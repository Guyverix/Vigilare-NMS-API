<?php

/*
  Testing SNMP this way will make it so that throw errors will not stop the
  application.  We WANT to see the throws to know if what we are doing is working
  or if we need to try something else.

  In this case, we are going to iterate until we are done testing to see
  if an SNMP query works or not and simply return success or failure as a string
  value.  The main script is going to decide if it is happy with those results
*/

// SNMP spews warnings we do not want to see.  Suppress for the duration of this script.
error_reporting(E_ERROR | E_PARSE);

$hostname=$argv[1];
$community=$argv[2];
$version=$argv[3];
// Optional stuff but if one is set, the others have to be in the same order
if (isset($argv[4])) { $timeout=(int)$argv[4]; } else { $timeout=(int)100000; }
if (isset($argv[5])) { $retries=(int)$argv[5]; } else { $retries=(int)3; }
if (isset($argv[6])) { $v3level=$argv[6]; } else { $v3level="v3level"; }
if (isset($argv[7])) { $v3protocol=$argv[7]; } else { $v3protocol="v3protocol"; }
if (isset($argv[8])) { $v3password=$argv[8]; } else { $v3password="v3password"; }
if (isset($argv[9])) { $v3privProtocol=$argv[9]; } else { $v3privProtocol="v3privProtocol"; }
if (isset($argv[10])) { $v3privPassword=$argv[10]; } else { $v3privPassword="v3privPassword"; }
if (isset($argv[11])) { $v3context=$argv[11]; } else { $v3context="v3context"; }
if (isset($argv[12])) { $v3engineId=$argv[12]; } else { $v3engineId="v3engineId"; }


if (empty($hostname) || empty($community) || empty($version)) {
  echo "FATAL: missing hostname, community or version";
  die(3);
}

snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);

function testSnmp($version, $testCommunity, $hostname , $timeout, $retries) {
  switch ($version) {
    case ($version == 1):
      $session=new SNMP(SNMP::VERSION_1, $hostname, $testCommunity, $timeout, $retries);
      $session->oid_output_format=SNMP_OID_OUTPUT_NUMERIC;
      break;
    case ($version == 2):
      $session=new SNMP(SNMP::VERSION_2c, $hostname, $testCommunity, $timeout, $retries);
      $session->oid_output_format=SNMP_OID_OUTPUT_NUMERIC;
      break;
    case($version == 3):
      $session=new SNMP(SNMP::VERSION_3, $hostname, $testCommunity, $timeout, $retries);
      $session->setSecurity( $v3level, $v3protocol, $v3password, $v3privProtocol, $v3privPassword, $v3context, $v3engineId);
      $session->oid_output_format=SNMP_OID_OUTPUT_NUMERIC;
      break;
    default:
      return "falure";
      break;
  }
  // We gotta be able to catch errs
  $session->exceptions_enabled = SNMP::ERRNO_ANY;
  // Set standards
  $session->max_oids = 40;

  try {
    // We dont care what the value is, just that we got one
//    $junk=$session->walk('1.3.6.1.4.1.2021.10.1');
    $junk=$session->walk('1.3.6.1.2.1.1.1.0');
    if (is_array($junk)) {
      echo "success";
    }
    else {
      echo "failure";
    }
  }
  catch (SNMPException $e) {
    // return var_dump($session);
    //$session->close();
    echo "failure";
  }
}
echo testSnmp($version, $community, $hostname, $timeout, $retries);
?>
