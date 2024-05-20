<?php
  /*
    This class will be dealing specifically with administration functions
    however, it is not a global access thing.

    Common use: CRUD for User accounts will use this API.

    It is important to note that we are using PEPPER not SALT
    to encrypt passwords before we drop them in the database here.
    Create, Review, Update, Delete

    Note that this class WILL save passwords in plain-text when running debug level logging
    While this is a security risk, Passwords are always goofy when errors happen.
    We also want an audit trail of who changed what, and when.

    This is likely the path that will get attacked most often since it will have
    a higher level of access.  Make very sure that we are as tight as possible
    in our settings and what exactly we are doing.
  */

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Domain\Admin\Admin;

// recycle the User validation, as admins need to follow the rules too :)
use App\Application\Validation\User\UserValidator as UserValidator;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ManageAdminAction extends AdminAction {

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
        $UserValues = $this->adminRepository->createUser($data, $this->pepper);
        break;
      case "register":
        $UserValues = $this->adminRepository->registerUser($data);  // They do not get a valid password here, they get an email to set the password from here
        break;
      case "adminRegister":
        $UserValues = $this->adminRepository->registerUser($data);  // They do not get a valid password here, they get an email to set the password from here
        break;
      case "review":
        $UserValues = $this->adminRepository->reviewUser($data);
        break;
      case "resendMail":
        $UserValues = $this->adminRepository->resendMail($data);    // This is for testing in its current state
        break;
      case "update":
        $UserValues = $this->adminRepository->updateUser($data);
        break;
      case "delete":
        if ( ! isset($data['id'])) {
          $this->logger->error("Invalid delete called without an account id.  ", $data);
          throw new HttpBadRequestException($this->request, "Never told account ID to delete");
        }
        else { $id = $data['id']; }
        $UserValues = $this->adminRepository->deleteUser($id);
        break;
      case "resetPassword":
        $UserValues = $this->adminRepository->resetPassword($data['username']);
        break;
      case "resetPasswordConfirm":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $UserValues = $this->adminRepository->resetPasswordConfirm($data['id'], $data['tpw'], $data['password'], $this->pepper);
        break;
      case "setPassword":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $this->passwordFailure($reserved, $forbidden, $data);
        $UserValues = $this->adminRepository->setPassword($data['id'], $data['tpw'], $data['password'], $this->pepper);
        break;
      case "updatePassword":       // Admin path
        $validator->updatePassword($data);
        $this->logger->debug("validation called for ", $data);
        // $this->passwordFailure($reserved, $forbidden, $data);  // This enforces complexity requirements even for admins
        // NOTE: admins can set passwords that do not have special characters (IE for some kind of automation) but still must be minimum character count
        $UserValues = $this->adminRepository->updatePassword($data['username'], $data['password'], $this->pepper);
        break;
      case "updatePasswordUsers":  // User path
        $validator->updatePassword($data);
        $this->logger->debug("validation called for ", $data);
        $this->passwordFailure($reserved, $forbidden, $data);
        $UserValues = $this->adminRepository->updatePasswordUsers($data['username'], $data['password'], $data['oldPassword'], $this->pepper);
        break;
      case "activate":
        $UserValues = $this->adminRepository->activateAccount($data['username']);
        break;
      case "deactivate":
        $UserValues = $this->adminRepository->deactivateAccount($data['username']);
        break;
      case "validate":
        $validator->setPassword($data);
        $this->logger->debug("validation called for ", $data);
        $UserValues = $this->adminRepository->validateAccount($data['username'], $data['password']);  // This is how we are going to filter down to new users, the pass is not encrypted here, but a random string we gave them previously
        break;
      case "test":
        $UserValues = $data;
        break;
      case "findUsersAll":
        $UserValues = $this->adminRepository->findUsersAll();
        break;
      default:
        $this->logger->error("Route called with no valid action set in URL.");
        throw new HttpBadRequestException($this->request, "Route called with no valid action set in URL.");
        break;
    }
    // All queries will return string FAILURE when the database does not accept something
    // Append the post data to logs even if it has sensitive values when failures happen
    $CheckUserValues = json_decode(json_encode($UserValues,1), true);
    if ( ! is_array($CheckUserValues[0]) && str_contains($CheckUserValues[0], 'FAILURE')) {
    // if (preg_match('/FAILURE/', $CheckUserValues)) {
      $this->logger->error("Failed to run " . $job . " successfully.  " . json_encode($data,1), $CheckUserValues);  // CheckUserValues must be array
      throw new HttpBadRequestException($this->request,$CheckUserValues[0]);  // must return string?
    }
    // We dont save user data here because passwords would be saved.
    $this->logger->info("Completed job " . $job, $UserValues);
    return $this->respondWithData($UserValues);
  }
}
