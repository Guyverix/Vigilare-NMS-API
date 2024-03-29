<?php
declare(strict_types=1);

namespace App\Application\Actions\EventCorrelation;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\EventCorrelation\EventCorrelationAction;
use App\Domain\EventCorrelation\EventCorrelationRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageEventCorrelationAction extends EventCorrelationAction {
  protected function action(): Response {
    $jobType=["create", "delete", "update", "find", "test", "family", "list"];  // Actions used for ECE changes

    // How to check if resolveArg is even going to work
    // before calling it and kicking an exception
    if ( empty($this->args["action"]) )   { $action="failure";}  else { $action=$this->resolveArg("action"); }
    if ( empty($this->args["relation"]) ) { $relation="orphan";} else { $relation=$this->resolveArg("relation"); }

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage EventCorrelation Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    // This will always be an array
    $data = $this->getFormData();
    $data['action'] = $action;
    $data['relation'] = $relation;
    // return $this->respondWithData($data);
    if("$action" == "create") {
      $this->logger->debug("ManageEventCorrelationAction.php action create " . json_encode($data,1));
      switch ($relation) {
        case 'parent':
          if ( ! array_key_exists('parentId', $data)) { $data['parentId'] = null; }
          $FindEventCorrelation=$this->eventCorrelationRepository->createEceGroups($data);
          break;
        case 'child':
          $FindEventCorrelation=$this->eventCorrelationRepository->createEceGroups($data);
          break;
        default:
          $FindEventCorrelation=$this->eventCorrelationRepository->createRule($data);
          break;
      }
    }
    elseif ($action == "find") {
      $FindEventCorrelation=$this->eventCorrelationRepository->findRule();
    }
    elseif ($action == "family") {
      $FindEventCorrelation=$this->eventCorrelationRepository->familyRule();
    }
    elseif ($action == "list") {
      $FindEventCorrelation=$this->eventCorrelationRepository->familyList();
    }
    elseif ($action == "delete") {
      $id = $data['id'];
      $FindEventCorrelation=$this->eventCorrelationRepository->deleteRule($id);
    }
    elseif ($action == "update") {
      if ($relation !== "orphan") {
        $FindEventCorrelation=$this->eventCorrelationRepository->updateEce($data);
      }
      else {
        $FindEventCorrelation=$this->eventCorrelationRepository->updateRule($data);
      }
    }
    elseif ($action == "test") {
      $FindEventCorrelation="test success (I am brainless)";
    }
    else { // debug is going to be the default
      $FindEventCorrelation=$data;  // this is an array returns same array
    }
    // Figure out if we got an error back from the DB query and return a 500 if we did
    if ( isset($FindEventCorrelation[0]) && ! is_object($FindEventCorrelation[0]) && isset($FindEventCorrelation[0]) && str_contains($FindEventCorrelation[0], 'FAILURE') ) {
      $this->logger->error("FAILURE eventCorrelation values for " . $action . " with values " . json_encode($data, 1));
      return $this->respondWithData($FindEventCorrelation, 500);
    }
    else {
      $this->logger->info("Find eventCorrelation values for " . $action . " with values " . json_encode($data, 1));
      return $this->respondWithData($FindEventCorrelation);
    } // end return
  } // end function
}  // end class
