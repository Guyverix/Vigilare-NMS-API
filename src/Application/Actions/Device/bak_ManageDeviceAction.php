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

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage Device Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }

    // All the different POST data we could have as an array
    $data = $this->getFormData();

    // Define each possible option for our actions
    if ("$action" == "create") {
      if (! isset($data['address']))         { $data['address']="255.255.255.255";}
      if (! isset($data['productionState'])) { $data['productionState']='1';}
      // if (! isset($data['hostname'])) { $data['hostname']='';}
      // Set hostname to IP address if not defined
      if ( empty($this->args["hostname"]) ) {
        if ( ! empty($data['hostname'])) {
          $hostname = $data['hostname'];
        }
        else {
          $hostname = '';
          // $data['hostname']='';
        }
      }
      else {
        $hostname = $this->resolveArg("hostname");
      }
      /* make damn sure we have what we need here */
      $validator = new DeviceValidator();
      $validator->create($data);

      $host = $this->deviceRepository->createHost($data);
      $this->logger->info("Created new host for monitoring: " . json_encode($data, 1 ));
      return $this->respondWithData($host);
    }
    elseif ("$action" == "properties") {
      $validator = new DeviceValidator();
      $validator->properties($data);
      $host = $this->deviceRepository->propertiesHost($data);
      $this->logger->info("Retrieve device properties " . json_encode($data, 1));
      return $this->respondWithData($host);
    }
    elseif ("$action" == "performance") {
//      $validator = new DeviceValidator();
//      $validator->performance($data);
      $host = $this->deviceRepository->findPerformance($data);
      $this->logger->info("Retrieve device performance " . json_encode($data, 1));
      return $this->respondWithData($host);
    }
    elseif ("$action" == "update") {
      /* make damn sure we have what we need here */
      // Define WHAT we are updating.
      if ( isset($data['component'])) {
        $update = $this->deviceRepository->updateProperties($data);
        $this->logger->info("Updated device properties for device ". json_encode($data,1));
        return $this->respondWithData($update);
      }
      else {
        // Defaults to a host update
        $validator = new DeviceValidator();
        $validator->update($data);
        $host = $this->deviceRepository->updateHost($data);
        $this->logger->info("Updated device " . json_encode($data, 1));
        $event = $this->deviceRepository->updateEvents($data);
        $this->logger->info("Updated active events for a device " . json_encode($data, 1));
        return $this->respondWithData($host);
      }
    }
    elseif ("$action" == "delete") {
      /* make damn sure we have what we need here */
      $data['id']=(int)$data['id'];

      $validator = new DeviceValidator();
      $validator->delete($data);

      $this->logger->info("Deleted host " . json_encode($data, 1));
      $host = $this->deviceRepository->deleteHost($data);
      return $this->respondWithData($host);
    }
    elseif ("$action" == "view") {
      $host = $this->deviceRepository->findAllHost();
      $this->logger->info("Device table from database queried for all hosts was retrieved.");
      return $this->respondWithData($host);
    }
    elseif ("$action" == "test" ) {
      /* make damn sure we have what we need here */
      $validator = new DeviceValidator();
      $validator->test($data);

      $host = ["test" => "success"];
      return $this->respondWithData($host);
    }
    elseif ("$action" == "debug" ) {
      $host = "Database Object Ping result  " . json_encode($this->db->ping(), 1 );
      // $host = get_defined_vars();
      // $host = json_encode($this->db, 1 );
      // $host =  print_r(var_dump($GLOBALS),1);  // HUGE!  > 500MB, too much for Postman
      return $this->respondWithData($host);
    }
    elseif ("$action" == "find") {
      /* make damn sure we have what we need here */
//      $validator = new DeviceValidator();
//      $validator->find($data);

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
        $this->logger->error("API queried for finding a specific host or IP address without a hostname or address given" . json_encode($host, 1) );
        throw new HttpBadRequestException($this->request, $host);
      }
      $this->logger->info("API queried for finding a specific host or IP address "  . json_encode($host, 1) );
      return $this->respondWithData($host);
    }
    else {
     // show expected argument
     $this->logger->debug("List supported action values");
     $x='';
     foreach ($jobType as $list) {
       $x = $x ." " . $list;
     }
     $supportedArguments="supported actions " . $x;
     unset ($x);
     throw new HttpBadRequestException($this->request, $supportedArguments);
    }
  } // end function
} // end class

