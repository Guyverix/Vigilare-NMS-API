<?php
declare(strict_types=1);


/* Using composer: composer require freedsx/snmp
   https://packagist.org/packages/freedsx/snmp
   https://github.com/FreeDSx/SNMP
   We are not going to bother with the apt snmp package
   since this appears better */

namespace App\Infrastructure\Persistence\Snmp;

use PDO;
use App\Domain\Snmp\Snmp;
use App\Domain\Snmp\SnmpNotFoundException;
use App\Domain\Snmp\SnmpRepository;

class DatabaseSnmpRepository2 implements SnmpRepository {
  private $host;
  private $oid;
  private $snmpResult;

  public function getOid($host, $oid) {

  return $result
  }

  public function getTable($host, $oid) {

  return $result
  }

  public function insertOidResult($host, $snmpResult) {

  return $result
  }

  public function insertOidTableResult($host, $snmpResult) {

  return $result
  }
}

