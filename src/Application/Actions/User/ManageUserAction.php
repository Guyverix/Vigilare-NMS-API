<?php
  /*
    This class will be dealing specifically with user accounts
    however, it is not a global access thing.  In general one account
    at a time, and will be limited on what it can do.
    Commonly, password resets, account updates, and account creates
    will use this API.

    It is important to note that we are using PEPPER not SALT
    to encrypt passwords before we drop them in the database here.
    Create, Review, Update, Delete

    A second route (/account) uses this class as well for the accountActivate, deactivate, validate paths
    This will be necessary as these will not have JWT tokens or access levels set.
    Validation that ONLY specific routes are accessable from this route must be done.

    Note that this class WILL save passwords in plain-text when running debug level logging

  */

declare(strict_types=1);

namespace App\Application\Actions\User;


use App\Domain\User\User;

use App\Application\Validation\User\UserValidator as UserValidator;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * @OA\Post(
 *     path="/user/{job}",
 *     summary="Manage user operations",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="job",
 *         in="path",
 *         required=true,
 *         description="Action to perform (e.g. create, delete)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="username", type="string"),
 *             @OA\Property(property="email", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User job completed"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */




class ManageUserAction extends UserAction {

  // Check only when needed if we have the correct password complexity
  public function checkPasswordReserved($password) {
    $exist='!@#$%^&*(){}[]\|/?<>:"\'';
    foreach(str_split($exist) as $char) {
      if (strpos($password, $char) !== false) {
        break;
      }
    }
    if (strpos($password, $char) === false) {
      return 1;
    }
    else {
      return 0;
    }
  }

  // Look for idiots who use single or double quotes in passwords to be "cute"
  public function checkPasswordForbidden($password) {
    $exist='\'"';
    foreach(str_split($exist) as $char) {
      if (strpos($password, $char) !== false) {
        break;
      }
    }
    if (strpos($password, $char) === false) {
      return 0;
    }
    else {
      return 1;
    }
  }

  // kick back an error when a user does not have reserved chars.
  // After thinking about it, I am going to allow single and double quotes since we have good input validation
  public function passwordFailure($reserved, $forbidden, $data) {
    if ($reserved !== 0) {
      $this->logger->error("Password does not meet complexity requirements" , $data);
      throw new HttpBadRequestException($this->request, "Password does not meet complexity requirements");
    }
  }

  protected function action(): Response {
    $data = $this->getFormData();
    if ( empty($this->args["job"]) ) { $job="failure";} else { $job=$this->resolveArg("job"); }
    if ( isset($data['id'])) { $data['id'] = (int)$data['id']; }  // need to make sure it is considered an int before calling validator..  Blah..
    if ( ! isset($data['password'])) { $data['password']=''; }
    $validator = new UserValidator();

    $reserved = $this->checkPasswordReserved($data['password']);
    $forbidden = $this->checkPasswordForbidden($data['password']);
    $data['frontendUrl'] = $this->frontendUrl;

    switch ($job) {
      case "create":
        $UserValues = $this->userRepository->createUser($data, $this->pepper);
        break;
      case "register":
        $UserValues = $this->userRepository->registerUser($data);  // They do not get a valid password here, they get an email to set the password from here
        break;
      case "review":
        $UserValues = $this->userRepository->reviewUser($data);
        break;
      case "resendMail":
        $UserValues = $this->userRepository->resendMail($data);    // This is for testing in its current state
        break;
      case "update":
        $UserValues = $this->userRepository->updateUser($data);
        break;
      case "adminUpdate":
        $UserValues = $this->userRepository->adminUpdateUser($data);
        break;
      case "delete":
        if ( ! isset($data['id'])) {
          $this->logger->error("Invalid delete called without an account id.  ", $data);
          throw new HttpBadRequestException($this->request, "Never told account ID to delete");
        }
        else { $id = $data['id']; }
        $UserValues = $this->userRepository->deleteUser($id);
        break;
      case "resetPassword":
        $UserValues = $this->userRepository->resetPassword($data['username']);
        break;
      case "resetPasswordConfirm":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $UserValues = $this->userRepository->resetPasswordConfirm($data['id'], $data['tpw'], $data['password'], $this->pepper);
        break;
      case "setPassword":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $this->passwordFailure($reserved, $forbidden, $data);
        $UserValues = $this->userRepository->setPassword($data['id'], $data['tpw'], $data['password'], $this->pepper);
        break;
      case "updatePassword":       // Admin path
        $validator->updatePassword($data);
        $this->logger->debug("validation called for ", $data);
        // NOTE: admins can set passwords that do not have special characters (IE for some kind of automation) but still must be minimum character count
        $UserValues = $this->userRepository->updatePassword($data['username'], $data['password'], $this->pepper);
        break;
      case "updatePasswordUsers":  // User path
        $validator->updatePassword($data);
        $this->logger->debug("validation called for ", $data);
        $this->passwordFailure($reserved, $forbidden, $data);
        $UserValues = $this->userRepository->updatePasswordUsers($data['username'], $data['password'], $data['oldPassword'], $this->pepper);
        break;
      case "activate":
        $UserValues = $this->userRepository->activateAccount($data['username']);
        break;
      case "deactivate":
        $UserValues = $this->userRepository->deactivateAccount($data['username']);
        break;
      case "validate":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $UserValues = $this->userRepository->validateAccount($data['username'], $data['password']);  // This is how we are going to filter down to new users, the pass is not encrypted here, but a random string we gave them previously
        break;
      default:
        $this->logger->error("Route called with no valid action set in URL.");
        throw new HttpBadRequestException($this->request, "Route called with no valid action set in URL.");
        break;
    }
    // All queries will return string FAILURE when the database does not accept something
    // Append the post data to logs even if it has sensitive values when failures happen
    if ( ! isset($UserValues[0])) { $UserValues[0] = ''; }
    if ( is_object($UserValues[0])) { $UserValues[0] = json_encode($UserValues[0],1) ; }
    if ( is_array($UserValues[0])) { $UserValues[0] = json_encode($UserValues[0],1) ; }
    if (str_contains($UserValues[0], 'FAILURE')) {
      $this->logger->error("Failed to run " . $job . " successfully.  " . json_encode($data,1), $UserValues);
      throw new HttpBadRequestException($this->request,$UserValues[0]);
    }
    // We dont save user data here because passwords would be saved.
    $this->logger->info("Completed job " . $job, $UserValues);
    return $this->respondWithData($UserValues);
  }
}
