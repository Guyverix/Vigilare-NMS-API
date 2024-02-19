<?php

declare(strict_types=1);

namespace App\Application\Validation\Utilities;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

/*
        Assert::stringNotEmpty($data[''], 'POST `` cannot be empty.');
        Assert::maxLength($data[''], #, 'POST `` be longer than # characters.');
*/

     /* ADD MORE VALIDATION HERE IF NEEDED IN THE FUTURE. ASSERT HAS INTERESTING
        METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */

class UtilitiesValidator extends Validator {
    public function __validate(array $data): void {
      Assert::stringNotEmpty($data['name'], 'POST `name` cannot be empty.');
      Assert::stringNotEmpty($data['oid'], 'POST `oid` cannot be empty.');
    }
    public function findAddress(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "address", "POST `address=x.x.x.x` is manditory");
      Assert::ipv4($data['address']);
    }

    public function findHostname(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "hostname", "POST `hostname=xxxx` or `hostname=w.x.y.z` is manditory");
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
    }

    public function checkIfKnown(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
    }

    public function GetIpAddresses(array $data): void {
      // this is going to need more validation in the future
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "cidr");
      Assert::stringNotEmpty($data['cidr']);
    }

    public function CheckIfKnownAddressesInRange(array $data): void {
      // this is going to need more validation in the future
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "checkIp");
      Assert::keyExist($data, "range");
      Assert::stringNotEmpty($data['checkIp']);
      Assert::stringNotEmpty($data['range']);
    }

    public function IpInNetwork(array $data): void {
      // this is going to need more validation in the future
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "netMask", "POST `netmask=xxxx` is manditory");
      Assert::keyExist($data, "address", "POST `address=x.x.x.x` is manditory");
      Assert::keyExist($data, "netAddress", "POST `netAddress=x.x.x.x` is manditory");
    }

    public function NmapPingScan(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::stringNotEmpty($data['subnet']);
    }

    public function NmapDeviceOpenPorts(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::ipv4($data['address']);
    }

    public function ping(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "hostname", "POST `hostname=xxxx` or `hostname=w.x.y.z` is manditory");
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
    }

    public function FindIpAddress(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "device", "POST `device=xxxx` is manditory");
      Assert::stringNotEmpty($data['device'], 'POST `device` cannot be empty.');
      Assert::minLength($data['device'], 4, 'POST `device` minimum length is 4 characters.');
      Assert::maxLength($data['device'], 255, 'POST `device` maximum length is 255 characters.');
    }

    public function Dig(array $data): void {
      Assert::isArray($data, 'POST `data` is not an array.');
      Assert::keyExist($data, "hostname", "POST `hostname=xxxx` is manditory");
      Assert::stringNotEmpty($data['hostname'], 'POST `hostname` cannot be empty.');
      Assert::minLength($data['hostname'], 4, 'POST `hostname` minimum length is 4 characters.');
      Assert::maxLength($data['hostname'], 255, 'POST `hostname` maximum length is 255 characters.');
    }
}
