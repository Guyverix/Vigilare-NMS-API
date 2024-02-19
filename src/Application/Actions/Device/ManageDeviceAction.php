<?php
declare(strict_types=1);

namespace App\Application\Actions\Device;

use App\Application\Validation\Device\DeviceValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ManageDeviceAction extends DeviceAction {
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $jobType=["view", "create", "update", "delete", "test", "find", "debug", "properties", "performance"]; // sanity check that we only are doing what we expect here

    // How to check if resolveArg is even going to work
    // before calling it and kicking an exception
    if ( empty($this->args["action"]) ) {
      $action="failure";
    }
    else {
      $action=$this->resolveArg("action");
    }

    // All the different POST data we could have as an array
    $data = $this->getFormData();

    // Setup our valiation now
    $validator = new DeviceValidator();

    switch ($action) {
    // Fail fast if we are never going to be able to do anything
    case in_array("$action", $jobType) == false:
      $x='';
      foreach ($jobType as $list) { $x = $x ." " . $list; }
      $jobTypeText="supported actions: " . $x;
      unset ($x);
      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage Device Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);
      break;
    case "create":
      // We can only create 2 things.  devices, and deviceGroups
      if (isset( $data['deviceGroup'] )) {
        $host = $this->deviceRepository->createDeviceGroup($data);
      }
      else {  // we are adding a device
        if (! isset($data['address']))         { $data['address']="255.255.255.255";}
        if (! isset($data['productionState'])) { $data['productionState']='1';}
        if ( empty($this->args["hostname"]) ) {
          if ( ! empty($data['hostname'])) {
            $hostname = $data['hostname'];
          }
          else {
            $hostname = '';
          }
        }
        else {
          $hostname = $this->resolveArg("hostname");
        }
        /* make damn sure we have what we need here */
        $validator->create($data);
        $host = $this->deviceRepository->createHost($data);
        $this->logger->info("Created new host for monitoring: " . json_encode($data, 1 ));
      }
      break;
    case "properties":
      $validator->properties($data);
      $host = $this->deviceRepository->propertiesHost($data);
      $this->logger->info("Retrieve device properties " . json_encode($data, 1));
      break;
    case "performance":
      // $validator->performance($data);
      $host = $this->deviceRepository->findPerformance($data);
      $this->logger->info("Retrieve device performance " . json_encode($data, 1));
      break;
    case "update":
      /* make damn sure we have what we need here */
      if (isset($data['deviceGroup'])) {
        $host = $this->deviceRepository->updateDeviceGroup($data);
        $this->logger->info("Updated deviceGroup and changed members " . json_encode($data,1));
      }
      elseif ( isset($data['component'])) {
        $host = $this->deviceRepository->updateProperties($data);
        $this->logger->info("Updated device properties for device ". json_encode($data,1));
      }
      else {
        // Defaults to a host update
        $validator->update($data);
        $host = $this->deviceRepository->updateHost($data);
        $this->logger->info("Updated device " . json_encode($data, 1));
        $event = $this->deviceRepository->updateEvents($data);
        $this->logger->info("Quiet update active events for a device " . json_encode($data, 1));
      }
      break;
    case "delete":
      if (isset($data['deviceGroup'])) {
        $host = $this->deviceRepository->deleteDeviceGroup($data);
        $this->logger->info("deviceGroup deleted " . json_encode($data,1));
      }
      else {
        $data['id']=(int)$data['id'];
        $validator->delete($data);
        $this->logger->info("Deleted host " . json_encode($data, 1));
        $host = $this->deviceRepository->deleteHost($data);
      }
      break;
    case "view":
      $host = $this->deviceRepository->findAllHost();
      $this->logger->info("Device table from database queried for all hosts was retrieved.");
      break;
    case "test":
      /* make damn sure we have what we need here */
      $validator->test($data);
      $host = ["test" => "success"];
      break;
    case "debug":
      $host = "Database Object Ping result  " . json_encode($this->db->ping(), 1 );
      // $host = get_defined_vars();
      // $host = json_encode($this->db, 1 );
      // $host =  print_r(var_dump($GLOBALS),1);  // HUGE!  > 500MB, too much for Postman
      break;
    case "find":
       if (isset($data['deviceGroup'])) {
        $host = $this->deviceRepository->findDeviceGroup();
        $this->logger->info("Find all deviceGroup names called");
      }
      elseif ( isset($data['deviceInDeviceGroup'])) {
        $host = $this->deviceRepository->findDeviceInDeviceGroup($data);
        $this->logger->info("Find all deviceGroups which contain device id" . json_encode($data, 1));
      }
      elseif ( isset($data['deviceGroupMonitors'])) {
        $host = $this->deviceRepository->findDeviceGroupMonitors($data);
        $this->logger->info("Find all monitors for a given deviceGroup" . json_encode($data, 1));
      }
      else {  // only looking for a device
        // $validator->find($data);
        if (! empty($data['address']) ) {
          $host = $this->deviceRepository->findAddress( $data );
        }
        elseif (! empty($data['hostname']) ) {
          $host = $this->deviceRepository->findHost( $data );
        }
        elseif (! empty($data['id']) ) {
          $host = $this->deviceRepository->findId( $data );
        }
        else {
          $host = ("Need either hostname or address sent for a search");
          $this->logger->error("manageDeviceAction find failed.  Host or IP address is manditory" . json_encode($host, 1) );
          throw new HttpBadRequestException($this->request, $host);
        }
      }
      $this->logger->info("manageDeviceAction find queried for finding a specific host or IP address "  . json_encode($host, 1) . " post data " .  json_encode($data,1));
      break;
    } // end switch
  $this->logger->info("manageDeviceAction call for " . $action . " query data " . json_encode($data,1));
  return $this->respondWithData($host);
  } // end function
} // end class

