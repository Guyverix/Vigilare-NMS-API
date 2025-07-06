<?php

declare(strict_types=1);

namespace App\Application\Validation\Device;

require '/opt/Vigilare-NMS-API/app/Logger.php';
use ExternalLogger ;
use Exception;

use App\Application\Validation\Validator;
/*
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
//use Slim\Exception\HttpSpecializedException;

*/
use Webmozart\Assert\Assert;
//use SimpleSAML\Assert\Assert;


/*   METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */


class DeviceValidator extends Validator {
    public $logger;

    public function __construct() {
       $logger = new ExternalLogger("validationError", 0, 0);
       $logger->loggerFile='/opt/Vigilare-NMS-API/logs/validationError.log';
       $this->logger=$logger;
    }

    // This is the initial catchall.  Kept as example IT IS NOT VALID
    public function __validate(array $data): void {
      Assert::isArray($data, 'Field `data` is not an array.');
      Assert::inArray("hostname", $data, "__POST `hostname=xxxx` is manditory");
      Assert::inArray("address", $data, "__POST `address=x.x.x.x` (or IPv6) is manditory");
      Assert::inArray("productionState", $data, "__POST `productionState=#` is manditory");
      Assert::stringNotEmpty($data['hostname'], '__Field `hostname` cannot be empty.');
      Assert::minLength($data['hostname'], 4, '__Field `hostname` minimum length is 4 characters.');
      Assert::maxLength($data['hostname'], 255, '__Field `hostname` maximum length is 255 characters.');
      Assert::stringNotEmpty($data['address'], '__Field `address` cannot be empty.');
      Assert::ipv4($data['address']);
      Assert::integer($data['productionState'], '__Field `productionState` is an integer between 0 6.');
      Assert::integer($data['id'], '__Field `id` is an integer between 0 6.');
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
      Assert::integer((int)$data['id'], 'POST `id` is an integer.');
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      Assert::minLength($data['hostname'], 4, 'POST `hostname` minimum length is 4 characters.');
      Assert::maxLength($data['hostname'], 255, 'POST `hostname` maximum length is 255 characters.');
      Assert::stringNotEmpty($data['address'], 'POST `address` cannot be empty.');
      Assert::ipv4($data['address']);
      Assert::integer((int)$data['productionState'], 'POST `productionState` is an integer between 0 6.');
    }

    public function properties(array $data): void {
       $this->logger->info("validating properties POST " . json_encode($data,1) );
       Assert::isArray($data, 'POST `data` is not an array.');
       Assert::keyExists($data, "id", "POST `id=xxx` is manditory");
    }

    public function performance(array $data): void {
       $this->logger->info("validating performance POST " . json_encode($data,1) );
       Assert::isArray($data, 'POST `data` is not an array.');
       Assert::keyExists($data, "hostname", "POST `hostname=xxx` is manditory");
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
