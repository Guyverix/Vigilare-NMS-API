<?php

declare(strict_types=1);

namespace App\Application\Actions\Discovery;
use App\Application\Validation\Discovery\DiscoveryValidator;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotImplementedException;
use Psr\Http\Message\ResponseInterface as Response;

class ManageDiscoveryAction extends DiscoveryAction {

  protected function action(): Response {
    $jobType=["create", "discover", "test", "debug", "ping", "search"]; // sanity check that we only are doing what we expect here

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
      $this->logger->error("Manage Discovery Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    // This can be empty, so dont get bit here if there are no posted vars
    // This will always be an array
    $data = $this->getFormData();

    // Always make id an explicit integer if it is defined
    if ( isset($data['id'])) {
      $data['id']=(int)$data['id'];
    }

    if ( empty($data['create'])) {
      $data['create'] = "none";
    }

    if("$action" == "create") {
      if ($data['create'] == "Device") {
        $validator = new DiscoveryValidator();
        if (isset($data['productionState'])) { $data['productionState']=(int)$data['productionState']; }
        $validator->CreateDevice($data);
        $CreateDevice=$this->discoveryRepository->CreateDevice($data);
        $this->logger->info("Create Device with values " . json_encode($data,1));
        return $this->respondWithData($CreateDevice);
      }
      elseif ($data['create'] == "DeviceFolder") {
        $validator = new DiscoveryValidator();
        $validator->CreateDeviceFolder($data);
        $templateValue=$this->discoveryRepository->CreateDeviceFolder($data);
        $this->logger->info("Create DeviceFolder with values " . json_encode($data,1));
        return $this->respondWithData($templateValue);
      }
      elseif ($data['create'] == "DevicePropertiesTemplate") {
        $validator = new DiscoveryValidator();
        $validator->DevicePropertiesTemplate($data);
        $templateValue=$this->discoveryRepository->CreateDevicePropertiesTemplate($data);
        // Clean object to be a basic array
        $this->logger->info("Create DevicePropertiesTemplate with values " . json_encode($data,1));
        // We now have 0 as template, and 1 as device we are discovering
        return $this->respondWithData($templateValue);
      }
      else {
        throw new HttpNotImplementedException($this->request, "Create needs POST with create=Device, DeviceFolder, DevicePropertiesTemplate");
      }
    }
    elseif ($action == "discover") {
      // We need the ID we are going to discover SNMP for
      if ( ! isset($data['id'] ) ) {
        throw new HttpBadRequestException($this->request, "Discovery needs a POST id value to work from Device table");
      }
      else {
        // We have our ID, if we do not have hostname and address, pull it now from Devices table
        $findDeviceDetail=$this->discoveryRepository->FindDeviceDetail($data);

        // Convert from object to simple array, due to fucking reasons
        $findDevilceDetail=json_decode(json_encode($findDeviceDetail,1), true);

        // Device table is truth, populate from there, not from POST
        foreach ($findDeviceDetail as $DeviceDetail) {
          if ( isset($DeviceDetail->hostname)) { $data['hostname']=$DeviceDetail->hostname; }
          if ( isset($DeviceDetail->address))  { $data['address']=$DeviceDetail->address; }
        }

        /*
          Fail if we do not have an IP address to work with.  SNMP discovery should only
          work against IP since things like VHOSTS etc can point at other things.  We are not
          going to run multiple queries against the same IP address just because it has
          a different hostname for "reasons"
        */
          if ( empty($data['address'])) {
            throw new HttpBadRequestException($this->request, "Unable to retrieve hostname and IP address from Device table.  Check your id value.");
        }

        /*
           Now we pull template values since this is a discovery
           either default to Device and A_Default, or what the user
           is specifically asking for
        */
        if ( ! isset($data['Class']) || ! isset($data['Name'])) {
          $findTemplate=array("Class" => "Device", "Name" => "A_Default");
          $findTemplateDefaultDeviceProperties=$this->discoveryRepository->FindTemplateDefaultDeviceProperties($findTemplate);
        }
        else {
          $findTemplate=array("Class" => $data['Class'], "Name" => $data['Name']);
          $findTemplateDefaultDeviceProperties=$this->discoveryRepository->FindTemplateDefaultDeviceProperties($findTemplate);
        }

        // We now have template values.  Merge the template into $data as it is "truth"
        foreach ($findTemplateDefaultDeviceProperties as $initialTemplateValues) {
          if (isset($initialTemplateValues->templateValue)) {
            $rawData=json_decode($initialTemplateValues->templateValue, True);
            // For discovery overwrite anything that was put in manually for communities
            $rawData['snmpMonitorTimeout']=(int)$rawData['snmpMonitorTimeout'];
            $rawData['snmpTries']=(int)$rawData['snmpTries'];
            $data=array_merge($data, $rawData);
          }
          else {
            throw new HttpBadRequestException($this->request, "Unable to retrieve template values for Class " . $data['Class'] . " and Name " . $data['Name']);
          }
        }
        if ( ! is_array($data['snmpCommunities']) ) { $data['snmpCommunities']=array($data['snmpCommunities']); }
        if ( ! is_array($data['snmpVersions']) ) { $data['snmpVersions']=array($data['snmpVersions']); }
        $validator = new DiscoveryValidator();
        $validator->FindDeviceSnmpSettings($data);
        $snmpDeviceValues=$this->discoveryRepository->FindDeviceSnmpSettings($data);
        // return $this->respondWithData($snmpDeviceValues);
      }
      $this->logger->info("Discovery run for finding SNMP values for device " . $data['hostname'] . ".  Found: " . json_encode($snmpDeviceValues,1));
      // return $this->respondWithData($snmpDeviceValues);

      // We have known good values at this point.  Merge returns into $data
      $data['snmpEnable']   = $snmpDeviceValues['snmpEnable'];
      $data['snmpVersion']  = $snmpDeviceValues['version'];
      $data['snmpCommunity']= $snmpDeviceValues['community'];
      $data['snmpPort']     = $snmpDeviceValues['snmpPort'];

      // cleanup working values in array so we have standard results in the database.
      unset($data['create']);
      // FINALLY insert data into database
      $CreateDiscoveredDeviceProperties=$this->discoveryRepository->CreateDiscoveredDeviceProperties($data);
      $this->logger->info("Created or updated device " . $data['hostname'] . ".  Inserted data is " . json_encode($CreateDiscoveredDeviceProperties,1));
      return $this->respondWithData($CreateDiscoveredDeviceProperties);
    }
    elseif("$action" == "ping") {
      return $this->respondWithData('pong');
    }
    elseif("$action" == "search") {
      if ( ! isset($data['Class']) || ! isset($data['Name'])) {
        $job = "Class and Name are manditory values in order to search";
        $this->logger->error("Failed to run " . $action . " Manditory parameters not set" );
        throw new HttpBadRequestException($this->request, $job);
      }
      else {
        $findTemplate=array("Class" => $data['Class'], "Name" => $data['Name']);
        $findTemplateDefaultDeviceProperties=$this->discoveryRepository->FindTemplateDefaultDeviceProperties($findTemplate);
        return $this->respondWithData($findTemplateDefaultDeviceProperties);
      }
    }
    elseif("$action" == "test") {
      $returnData = ["test" => "success"];
    }
    elseif("$action" == "debug") {
      $returnData = "Return data variable  " . json_encode($data,1);
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
      $this->logger->warning("Discovery path called without stating supported action");
      throw new HttpBadRequestException($this->request, $supportedArguments);
    }
    // We are assuming a success at this point
    // return any data we got back.  All returns
    // must be named $returnData from the different
    // calls, for this to work.
//    $this->logger->info("Discovery called " . $action . " " . json_encode($data, 1));
//    return $this->respondWithData($returnData);
    return $this->respondWithData($data);
  }
}

