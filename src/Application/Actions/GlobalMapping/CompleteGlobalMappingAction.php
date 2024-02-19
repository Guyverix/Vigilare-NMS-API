<?php
declare(strict_types=1);

namespace App\Application\Actions\GlobalMapping;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class CompleteGlobalMappingAction extends GlobalMappingAction {
  protected function action(): Response {
    $action=$this->resolveArg("action") ?? "unset";  // Dont bother going furhter if we dont have an action type

    $job=$this->resolveArg("job") ?? "unset";        // dont bother going further if we do not have a job type
    $jobType=["view", "create", "update", "delete", "test", "find"]; // sanity check that we only are doing what we expect here

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$job", $jobType) ) {
      $job = "No valid job type set.  Try: view create update delete";
      $this->logger->error("Global Mapping no valid job type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }

    // All the different POST data we could have?
    $data = $this->getFormData();
    $hostname = $data['hostname'] ?? "unset" ;
    $iteration = $data['iteration'] ?? "unset";
    $oid = $data['oid'] ?? "unset" ;

    $perfStorage = $data['perfStorage'] ?? "unset" ;                // Storage types supported: database graphite databaseMetric (future? rrd influxdb kibana)
    $perfStorageType = ["database", "graphite", "databaseMetric"] ; // List to validate against

    $monitorName = $data['monitorName'] ?? "unset" ;
    $monitorCommand = $data['monitorComand'] ?? "unset" ;

/*
    Unused logic:
    $monitorType = $data['monitorType'] ?? "unset" ;        // Define what kind of monitor we are working with: NRPE, SNMP, Command, Curl
    $monitorTypeList = ["snmp", "nrpe", "shell", "curl"] ;  // Define only supported types of mappings

    if ( ! in_array("$monitorType", $monitorTypeList) &&  $job !== "view") {
      $job = "No valid moniotoring type set.  Try: snmp nrpe command curl.  Unimplemented: " . $monitorType;
      $this->logger->error("No valid monitoring type given for requested job (" . $job . ") in action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);
    }
*/

    if ($job == "test") {
    }
    // host table changes singleHost
    elseif ($action == "host") {
      switch ($job) {
        case "view":
          $data = $this->globalmappingRepository->viewGlobalMappingHost();
          break;
        case "find":
          $data = $this->globalmappingRepository->findGlobalMappingHost($data);
return $this->respondWithData($data);
          break;
        case "create":
          $data = $this->globalmappingRepository->createGlobalMappingHost($data);
          break;
        case "update":
          $data = $this->globalmappingRepository->updateGlobalMappingHost($data);
          break;
        case "delete":
          $data = $this->globalmappingRepository->deleteGlobalMappingHost($data);
          break;
      }
    }
    // hostGroup table changes only SINGLE HOSTGROUP SAVE AS JSON!
    elseif ($action == "hostGroup") {
      switch ($job) {
        case "view":
          $data = $this->globalmappingRepository->viewGlobalMappingHostGroup();
          break;
        case "create":
          $data = $this->globalmappingRepository->createGlobalMappingHostGroup($data);
          break;
        case "update":
          $data = $this->globalmappingRepository->updateGlobalMappingHostGroup($data);
          break;
        case "delete":
          $data = $this->globalmappingRepository->deleteGlobalMappingHostGroup($data);
          break;
      }
    }
    // hostAttribute table changes only SINGLE HOST  K => V pairing!
    elseif ($action == "hostAttribute") {
      switch ($job) {
        case "view":
          $data = $this->globalmappingRepository->viewGlobalMappingHostAttribute();
          break;
        case "create":
          $data = $this->globalmappingRepository->createGlobalMappingHostAttribute($data);
          break;
        case "find":
            $data = $this->globalmappingRepository->findGlobalMappingHostAttribute($data);
          break;
        case "update":
          $data = $this->globalmappingRepository->updateGlobalMappingHostAttribute($data);
          break;
        case "delete":
          $data = $this->globalmappingRepository->deleteGlobalMappingHostAttribute($data);
          break;
      }
    }
    // trapEventMap table single OID
    elseif ($action == "trap") {
      switch ($job) {
        case "view":
          $data = $this->globalmappingRepository->viewGlobalMappingTrap();
          break;
        case "create":
          $data['pre_processing']= preg_replace('/&quot/', '"', $data['pre_processing']);
          $data['post_processing']= preg_replace('/&quot/', '"', $data['post_processing']);
          $data = $this->globalmappingRepository->createGlobalMappingTrap($data);
          break;
        case "update":
          $data['pre_processing']= preg_replace('/&quot/', '"', $data['pre_processing']);
          $data['post_processing']= preg_replace('/&quot/', '"', $data['post_processing']);
          $data = $this->globalmappingRepository->updateGlobalMappingTrap($data);
          break;
        case "delete":
          $data = $this->globalmappingRepository->deleteGlobalMappingTrap($data);
          break;
      }
    }
    // monitoringPoller table single command
    elseif ($action == "poller") {
      switch ($job) {
        case "view":
          if (! empty($data['id'] )) {
            $data = $this->globalmappingRepository->findGlobalMappingPoller($data);
          }
          else {
            $data = $this->globalmappingRepository->viewGlobalMappingPoller();
          }
          break;
        case "create":
          $data = $this->globalmappingRepository->createGlobalMappingPoller($data);
          break;
        case "update":

          if ( empty($data['id'] )) {
            $data = "No id given for update";
            throw new HttpBadRequestException($this->request, $data);
          }
          else {
            $data = $this->globalmappingRepository->updateGlobalMappingPoller($data);
          }

          break;
        case "delete":
          if ( empty($data['id'] )) {
            $data = "No id given for deletion";
            throw new HttpBadRequestException($this->request, $data);
          }
          else {
            $data = $this->globalmappingRepository->deleteGlobalMappingPoller($data);
          }
          break;
      }
    }
    // TBD.  Possible move templates to database, although I like filesystem better TBH.
    elseif ($action == "template") {
      switch ($job) {
        case "view":
          $data = $this->globalmappingRepository->viewGlobalMappingTemplate();
          break;
        case "create":
          $data = $this->globalmappingRepository->createGlobalMappingTemplate($data);
          break;
        case "update":
          $data = $this->globalmappingRepository->updateGlobalMappingTemplate($data);
          break;
        case "delete":
          $data = $this->globalmappingRepository->deleteGlobalMappingTemplate($data);
          break;
      }
    }
    else {
      // If we do not have a valid mapping action, return a get stuffed error
      $data = "No valid mapping action called.  Try: host hostAttribute hostGroup trap poller template.  Not implemented: ($action)";
      $this->logger->error("Global Mapping route called with no action valid set in URL.  Attempted: ($action)");
      throw new HttpBadRequestException($this->request, $data);
    }
    if ( is_array($data)) {
      $this->logger->info("Global Mapping API call for " . $action . " running job " . $job . " with args: ", $data);
    }
    else {
      $this->logger->info("Global Mapping API call for " . $action . " running job " . $job . " with args: " . $data);
    }
    return $this->respondWithData($data);
  } // end function
} // end class

