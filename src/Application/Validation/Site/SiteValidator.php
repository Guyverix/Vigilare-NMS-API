<?php

declare(strict_types=1);

namespace App\Application\Validation\Site;

//require '/opt/Vigilare-NMS-API/app/Logger.php';
//use ExternalLogger ;
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


/*   METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */


class SiteValidator extends Validator {
//    public $logger;

    public function __construct() {
     /*
       $logger = new ExternalLogger("validationError", 0, 0);
       $logger->loggerFile='/opt/Vigilare-NMS-API/logs/validationError.log';
       $this->logger=$logger;
     */
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

    public function both(array $data): void {
      Assert::keyExists($data, "id", "POST `id=###` is manditory");
      Assert::keyExists($data, "group", "POST `group=###` is manditory");
    }

    public function id(array $data): void {
      Assert::keyExists($data, "id", "POST `id=###` is manditory");
    }

    public function group(array $data): void {
      Assert::keyExists($data, "group", "POST `group=###` is manditory");
    }
}
