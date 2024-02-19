<?php
declare(strict_types=1);

namespace App\Infrastructure\Shared\Snmp;

use SNMP as InternalSNMP;
//use PDO;
use App\Domain\Snmp\Snmp;
use App\Domain\Snmp\SnmpNotFoundException;
use App\Domain\Snmp\SnmpRepository;

class SharedSnmpRepository implements SnmpRepository {
  private $host;
  private $oid;
  private $community;
  private $version;
  private $snmpResult;
  private $data;
  private $value;

//  public function __construct() {}
/*
  public SNMP::__construct(
    int $version,
    string $hostname,
    string $community,
    int $timeout = -1,
    int $retries = -1
  )
*/

//  public function snmpoid($array):array {
//  public function snmpoid($hostname, $community, $oid) {
//  return "works";
//  }

  public function snmpoid($hostname, $community, $oid) {
    $version =2;
    $timeout=1;
    $retries=1;
//    $snmpClass = new InternalSNMP((int)"SNMP::VERSION_2c", "$hostname", "$community", 2 ,2 );
//    $snmpClass = new InternalSNMP(SNMP::VERSION_2c, "$hostname", "$community", 2 ,2 );
//    $data[] = $snmpClass->get("$oid");
//    return phpinfo();
  }


/*
  public function snmpoid($hostname, $community, $oid) {
    snmp_set_quick_print(1);
    snmp_set_enum_print(0);
    $retval = array();
    $raw = snmpget($hostname, $community, $oid);
    $retval += [$raw];
    return($retval);
  }
*/
  public function snmptable($hostname, $community, $oid) {
    snmp_set_oid_numeric_print(1);
    snmp_set_quick_print(0);
    snmp_set_enum_print(0);

    $retval = array();
    $raw = @snmprealwalk($hostname, $community, $oid);

    $prefix_length = 0;

    if ( ! is_array($raw)) { 
      $oidsReturned=0;
    }
    else {    
      $oidsReturned = count($raw);
    }

    /* if someone screwed up and queried an oid for a table reply */
    if ( $oidsReturned == 1 ) {
        foreach ($raw as $key => $value) {
        if ($prefix_length == 0) {
          // don't just use $oid's length since it may be non-numeric
          $prefix_elements = count(explode('.',$oid));
          $tmp = '.' . strtok($key, '.');
          while ($prefix_elements > 1) {
            $tmp .= '.' . strtok('.');
            $prefix_elements--;
          }
          $tmp .= '.';
          $prefix_length = strlen($tmp);
        }
        $key = substr($key, $prefix_length);
        $index = explode('.', $key, 2);
        isset($retval[$index[0]]) or $retval[$index[0]] = array();
        isset($firstrow) or $firstrow = $index[0];
        $retval[0][$index[0]] = $value;
      }

      // check for holes in the table and fill them in
      foreach ($retval[$firstrow] as $key => $tmp) {
        foreach($retval as $check => $tmp2) {
          if (! isset($retval[$check][$key])) {
            $retval[$check][$key] = '';
          }
        }
      }
    }
    elseif ( $oidsReturned == 0 ) {
      $retval[0][0]='nothing returned';
    }
    else {
      foreach ($raw as $key => $value) {
        if ($prefix_length == 0) {
          // don't just use $oid's length since it may be non-numeric
          $prefix_elements = count(explode('.',$oid));
          $tmp = '.' . strtok($key, '.');
          while ($prefix_elements > 1) {
            $tmp .= '.' . strtok('.');
            $prefix_elements--;
          }
          $tmp .= '.';
          $prefix_length = strlen($tmp);
        }
        $key = substr($key, $prefix_length);
        $index = explode('.', $key, 2);
        isset($retval[$index[1]]) or $retval[$index[1]] = array();
        isset($firstrow) or $firstrow = $index[1];
        $retval[$index[1]][$index[0]] = $value;
      }

      // check for holes in the table and fill them in
      foreach ($retval[$firstrow] as $key => $tmp) {
        foreach($retval as $check => $tmp2) {
          if (! isset($retval[$check][$key])) {
            $retval[$check][$key] = '';
          }
        }
      }
    }
    return($retval);
  }


  public function returnSnmpOid($request): array {
    $oid=$request['oid'];
    $hostname=$request['hostname'];
    $community=$request['community'];
    $result[] = $this->snmpoid($hostname, $community, $oid);
    return $result ;
  }

  public function returnSnmpTable($request): array {
    $oid=$request['oid'];
    $hostname=$request['hostname'];
    $community=$request['community'];
    $result = $this->snmptable($hostname, $community, $oid);
    return $result ;
  }

  public function testSnmpOid($request): array {
    return $request;
  }

  public function insertOidResult($value) {

    return $value ;
  }

  public function insertOidTableResult($value) {

    return $value ;
  }
}

