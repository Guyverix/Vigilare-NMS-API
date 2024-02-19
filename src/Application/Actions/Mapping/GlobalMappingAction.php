<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class GlobalMappingAction2 extends MappingAction {
  protected function action(): Response {
    $action=$this->resolveArg("action") ?? "unset";  // Dont bother going furhter if we dont have an action type

    $job=$this->resolveArg("job") ?? "unset";        // dont bother going further if we do not have a job type
    $jobType=["view", "create", "update", "delete"]; // sanity check that we only are doing what we expect here

    // All the different POST data we could have
    $data = $this->getFormData();
    $hostname = $data['hostname'] ?? "unset" ;
    $iteration = $data['iteration'] ?? "unset";
    $oid = $data['oid'] ?? "unset" ;

    $perfStorage = $data['perfStorage'] ?? "unset" ;                // Storage types supported: database graphite databaseMetric (future? rrd influxdb kibana)
    $perfStorageType = ["database", "graphite", "databaseMetric"] ; // List to validate against

    $monitorName = $data['monitorName'] ?? "unset" ;
    $monitorCommand = $data['monitorComand'] ?? "unset" ;

    $monitorType = $data['monitorType'] ?? "unset" ;        // Define what kind of monitor we are working with: NRPE, SNMP, Command, Curl
    $monitorTypeList = ["snmp", "nrpe", "shell", "curl"] ;  // Define only supported types of mappings


    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$job", $jobType) ) {
      $job = "No valid job type set.  Try: view create update delete";
      $this->logger->error("No valid job given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);
    }

    if ( ! in_array("$monitorType", $monitorTypeList)) {
      $job = "No valid moniotoring type set.  Try: snmp nrpe command curl";
      $this->logger->error("No monitoring type given for requested job (" . $job . ") in action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);
    }

    // host table changes singleHost
    if ($action == "host") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // hostGroup table changes only SINGLE HOSTGROUP
    elseif ($action == "hostGroup") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // hostAttribute table changes only SINGLE HOST
    elseif ($action == "hostAttribute") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // trapEventMap table single OID
    elseif ($action == "trap") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // monitoringPoller table single command
    elseif ($action == "nrpe") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // monitoringPoller table single shell (nagios) command
    elseif ($action == "shell") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // monitoringPoller table single OID
    elseif ($action == "snmp") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // monitoringPoller talble single URL
    elseif ($action == "curl") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    // TBD.  Possible move templates to database, although I like filesystem better TBH.
    elseif ($action == "template") {
      switch ($job) {
        case "view":
          break;
        case "create":
          break;
        case "update":
          break;
        case "delete":
          break;
      }
    }
    else {
      // If we do not have a valid mapping action, return a get stuffed error
      $action = "No valid action called.  Try: host trap nrpe shell snmp curl template";
      $this->logger->error("Route called with no action set in URL.");
      throw new HttpBadRequestException($this->request, $action);
      return $this->respondWithData($event);
    }












/*  OLD version
    $data=$this->getFormData();
//     We are going to need all POST set at least for defaults if
//       we are going to make reliable database inserts
//
    $valid=200; // assume success first

    if (! isset($data['oid'])) {
      $valid=405;  // respond with a not allowed.  oid is manditory
      $mapping="OID value is manditory to create a new mapping!  Please try again.";
      $this->logger->error("Attempted to create a new mapping without an oid value set");
    }

    if (! isset($data['display_name']))    { $data['display_name']="unknown";}
    if (! isset($data['severity']))        { $data['severity']='1';}
    if (! isset($data['pre_processing']))  { $data['pre_processing']='';}
    if (! isset($data['type']))            { $data['type'] = 1; }
    if (! isset($data['parent_of']))       { $data['parent_of']='';}
    if (! isset($data['child_of']))        { $data['child_of']='';}
    if (! isset($data['age_out']))         { $data['age_out']=86400 ;}
    if (! isset($data['post_processing'])) { $data['post_processing']='';}

    // Return success or failure

    $validation=$this->mappingRepository->findOid($data);
    if (!empty($validation)) {
      $mapping="Oid Value already mapped.  Use update API";
      $valid=405;
    }

    if ( $valid !== 405) {
      // Send the $data array over for insert
      $mapping = $this->mappingRepository->createMapping($data);
      $this->logger->info("Added new trap mapping for " . $data['oid']);
      return $this->respondWithData($mapping);
    }
    else {
      throw new HttpBadRequestException($this->request, $mapping);
      return $this->respondWithData($mapping);
    }
*/
  } // end function
} // end class

