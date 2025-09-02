<?php

declare(strict_types=1);

namespace App\Application\Validation\Discovery;

//require '/opt/Vigilare-NMS-API/app/Logger.php';
require __DIR__ . '/../../../../app/Logger.php';
use ExternalLogger;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;
//use SimpleSAML\Assert\Assert;


/*   METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */


class DiscoveryValidator extends Validator {
    public $logger;

    public function __construct() {
       $logger = new ExternalLogger("validationError", 0, 0);
       $logger->loggerFile=__DIR__ . '/../../../../logs/validationError.log';
       $this->logger=$logger;
    }

    // This is the initial catchall example.
    public function __validate(array $data):void {
      Assert::isArray($data, 'Field `data` is not an array.');
    }

    public function FindTemplateDefaultDeviceProperties(array $data): void {
      Assert::isArray($data, 'Field `data` is not an array.');
      Assert::keyExists($data, "create", "POST `create=xxxx` is manditory");
      Assert::stringNotEmpty($data['create'], 'Field `create` cannot be empty.');
      Assert::minLength($data['create'], 4, 'Field `create` minimum length is 4 characters.');
      Assert::stringNotEmpty($data['Name'], 'Field `Name` cannot be empty.');
      Assert::integer($data['id'], 'Field `id` is an integer between 0 6.');
    }

    public function FindDeviceSnmpSettings(array $data): void {
      Assert::isArray($data, 'Field `data` is not an array.');
      Assert::isArray($data['snmpCommunities'], 'Field `communities` is not an array.');
      Assert::isArray($data['snmpVersions'], 'Field `versions` is not an array.');
      Assert::keyExists($data, "hostname", "POST `hostname=xxxx` is manditory");
      Assert::keyExists($data, "snmpCommunities", "POST `snmpCommunities=ARRAY` is manditory");
      Assert::keyExists($data, "snmpVersions", "POST `snmpVersions=ARRAY` is manditory");
      Assert::integer($data['snmpMonitorTimeout'], 'Field `snmpMonitorTimeout` is an integer in milliseconds.  Try 100000');
      Assert::integer($data['snmpTries'], 'Field `snmpTries` is an integer. Try 2');
      Assert::integer($data['id'], 'Field `id` is an integer');
    }


    public function create(array $data): void {
      $this->logger->info("validating create POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists($data, "hostname", "POST `hostname=xxxx` is manditory");
      Assert::keyExists( $data,"address", "POST `address=x.x.x.x` (or IPv6) is manditory");
      Assert::keyExists($data, "productionState", "POST `productionState=#` is manditory");
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      Assert::minLength($data['hostname'], 4, 'POST `hostname` minimum length is 4 characters.');
      Assert::maxLength($data['hostname'], 255, 'POST `hostname` maximum length is 255 characters.');
      Assert::stringNotEmpty($data['address'], 'POST `address` cannot be empty.');
      Assert::ipv4($data['address']);
      Assert::range($data['productionState'], 0, 6, 'POST `productionState` is an integer between 0 6.');
    }

    public function DevicePropertiesTemplate(array $data): void {
      $this->logger->info("validating DevicePropertiesTemplate POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists($data, "Class", "POST `Class=xxx` is manditory");
      Assert::keyExists($data, "Name", "POST `Name=xxx` is manditory");
      Assert::keyExists($data, "templateValue", "POST `templateValue=Json string` is manditory");
      Assert::keyExists($data, "DeviceFolder", "POST `DeviceFolder=Json string` is manditory");
    }

    public function CreateDeviceFolder(array $data): void {
      $this->logger->info("validating CreateDeviceFolder POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists($data, "DeviceFolder", "POST `DeviceFolder=Json string` is manditory");
    }

    public function CreateDevice(array $data): void {
      $this->logger->info("validating CreateDevice POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      if ( array_key_exists("hostname", $data) ) {
        Assert::keyExists($data, "hostname", "POST `hostname=xxxx` is manditory");
        Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      }
      else {
        Assert::keyExists($data, "Device", "POST `Device=XXX` is manditory");
        Assert::stringNotEmpty($data['Device'], 'POST `Device` cannot be empty.');
      }
      Assert::keyExists($data, "address", "POST `address=x.x.x.x` is manditory");
      Assert::stringNotEmpty($data['address'], 'POST `address` cannot be empty.');
      Assert::ipv4($data['address']);
      Assert::keyExists($data, "productionState", "POST `productionState=#` is manditory");
      Assert::integer($data['productionState'], 'POST `productionState` is an integer between 0 6.');
      Assert::range($data['productionState'], 0, 6, 'POST `productionState` is an integer between 0 6.');
    }

    public function find(array $data): void {
      $this->logger->info("validating find POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      if ( array_key_exists("hostname", $data) ) {
        Assert::keyExists($data, "hostname", "POST `hostname=xxxx` is manditory");
        Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      }
      else {
        Assert::keyExists($data, "address", "POST `address=x.x.x.x` is manditory");
        Assert::stringNotEmpty($data['address'], 'POST `address` cannot be empty.');
        Assert::ipv4($data['address']);
      }
    }

    public function update(array $data): void {
      $this->logger->info("validating update POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists($data, "hostname", "POST `hostname=xxxx` is manditory");
      Assert::keyExists( $data,"address", "POST `address=x.x.x.x` (or IPv6) is manditory");
      Assert::keyExists($data, "productionState", "POST `productionState=#` is manditory");
      Assert::keyExists($data, "id", "POST `id=#` is manditory");
      Assert::integer($data['id'], 'POST `id` is an integer.');
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      Assert::minLength($data['hostname'], 4, 'POST `hostname` minimum length is 4 characters.');
      Assert::maxLength($data['hostname'], 255, 'POST `hostname` maximum length is 255 characters.');
      Assert::stringNotEmpty($data['address'], 'POST `address` cannot be empty.');
      Assert::ipv4($data['address']);
      Assert::integer($data['productionState'], 'POST `productionState` is an integer between 0 6.');
    }

    public function delete(array $data): void {
      try {
      $this->logger->info("validating delete POST " . json_encode($data,1) );
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists($data, "id", "POST `id=#` is mandatory to delete a device");
      Assert::integer($data['id'], 'POST `id` is not an integer.');
      }
      catch (InvalidArgumentException $exception) {
        throw new ValidationException($exception->getMessage());
      }
    }

    public function test($data):void {
      try {
        $this->logger->info("validating test POST " . json_encode($data,1) );
        Assert::isArray($data, 'POST `data` is not an array.');
        Assert::keyExists($data, "id", "POST `id=#` is mandatory to pass this test");
      }
      catch (InvalidArgumentException $exception) {
        throw new ValidationException($exception->getMessage());
      }
    }
    public function view($data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
    }

    public function debug($data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
    }

    public function testFails($data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExists("failField", $data, "POST`failField=xxxx` is manditory to make this pass");
    }
}
