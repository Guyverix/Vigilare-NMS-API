<?php
declare(strict_types=1);

namespace App\Application\Actions\TEMPLATE;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\TEMPLATE\TEMPLATEAction;
use App\Domain\TEMPLATE\TEMPLATERepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageTEMPLATEAction extends TEMPLATEAction {
  protected function action(): Response {

    // Define the allowed jobs this class can do
    $jobType = ['task1', 'task2', 'task3'];

    /* BOILERPLATE
       This should be a generic way for each route to vet the supported arguments
       everything SHOULD be done this way if at all possible
    */
    // check if resolveArg is even going to work before calling it and kicking an exception
    if ( empty($this->args["action"]) ) { $action="failure";} else { $action=$this->resolveArg("action"); }

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("ManageTEMPLATE Action no valid action type defined for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    // pull in any form data we may have
    $data = $this->getFormData();

    /* END BOILERPLATE */

    switch ($action) {
      case 'task1':
        $queryResult = $this->templateRepository->task1($data);
        break;
      case 'task2':
        $queryResult = $this->templateRepository->task2($data);
        break;
      case 'task3':
        $queryResult = $this->templateRepository->task3($data);
        break;
      default:
        $this->logger->error("Route called with no valid action set in URL.");
        throw new HttpBadRequestException($this->request, "Route called with no valid action set in URL.");
        break;
    }
    // Assuming no errors, log the query and return the data
    $this->logger->info("Completed " . $action, $data);
    return $this->respondWithData($queryResult);
  }
}

