#!/usr/bin/php

<?php
/*
   This file will call the template file to create the values to forward to graphite.
   The logic is that this is called with host, data to be parsed, and rawOid value,
   the oid value is used to figure out which template will give us our return data.
   Once the data has been returned, then this will finally forward our metrics to Graphite
*/

/*
  The next version of this should have the graphite object passed as well
  so we do not need to create a second object here for something that exists
  in the parent calling script
*/

/*
  For manual testing we have to manually add the Class for Curl.  daemonFunctions.php
  normally has this already as it is called via the daemon.  We are bypassing that part
  for testing of the insert data manually.
*/
require_once __DIR__ . '/../app/Curl.php';
$apiHost="http://127.0.0.1";
$apiPort=8002;

// UTILITY FUNCTIONS for database push
require_once __DIR__ . '/../src/Infrastructure/Shared/Functions/daemonFunctions.php';

class MetricParsingDatabaseTest {
  public $hostname;
  public $dataToBeInserted;
  public $checkName;
  public $returnArrayValues;

  public function __construct() {}
  public function parseRaw($hostname, $dataToBeInserted, $checkName, $cycle = null, $type = null) {
    if (file_exists(__DIR__ . "/template_" . $checkName . ".php")) {
      // This should return our array
      echo "Found template file: " . __DIR__ . "/template_" . $checkName . ".php\n";
      require __DIR__ . "/template_" . $checkName . ".php";
    }
    else{
      // Return that we failed to find a template file
      echo "No template file found at: " . __DIR__ . "/template_" . $checkName . ".php\n";
      return 1;
    }
  }
} // end CLASS

function sendMetricToDatabase($hostname, $dataToBeInserted, $checkName, $cycle = null, $type = null) {
  if (! isset($metricData)) {
    // This should make an object to use for metrics
    $metricData=new MetricParsingDatabaseTest();
  }

  // We should now have a basic object called $metricData created
  $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  $returnArray = $metricData->returnArrayValues;
  //print_r($returnArray);

  if (empty($returnArray)) {
    echo "you failed to return data for " . $checkName . "\n";
    return 1;
  }

  echo "Would have pushed hostname " . $hostname . "\n";
  echo "Would have pushed checkName " . $checkName . "\n";
  echo "Would have pushed JSON values: \n" . $returnArray . "\n";
  // echo "Array style decoded:\n" . print_r(json_decode($returnArray,1)) . "\n"; // DEBUG
  // Attempt to use function to insert data into the database
  // sendPerformanceDatabase($hostname, $checkName, $returnArray);  // REAL CAUTION

  // Do NOT allow memory bleeds.  Unset stuff ASAP
  unset ($returnArray);
  unset ($metricData->returnArrayValues);
  return 0;
}


/*
// Routing SNMP info
$checkName='1.3.6.1.2.1.4.21.1';
$hostname='test.foo.bar.iwillfearnoevil.com';
$dataToBeInserted='{"iso.3.6.1.2.1.4.21.1.1.0.0.0.0":"0.0.0.0", "iso.3.6.1.2.1.4.21.1.1.127.0.0.0":"127.0.0.0", "iso.3.6.1.2.1.4.21.1.1.192.168.0.0":"192.168.0.0", "iso.3.6.1.2.1.4.21.1.2.0.0.0.0":"10", "iso.3.6.1.2.1.4.21.1.2.127.0.0.0":"1", "iso.3.6.1.2.1.4.21.1.2.192.168.0.0":"10", "iso.3.6.1.2.1.4.21.1.3.0.0.0.0":"1", "iso.3.6.1.2.1.4.21.1.3.127.0.0.0":"0", "iso.3.6.1.2.1.4.21.1.3.192.168.0.0":"0", "iso.3.6.1.2.1.4.21.1.7.0.0.0.0":"192.168.0.1", "iso.3.6.1.2.1.4.21.1.7.127.0.0.0":"0.0.0.0", "iso.3.6.1.2.1.4.21.1.7.192.168.0.0":"0.0.0.0", "iso.3.6.1.2.1.4.21.1.8.0.0.0.0":"4", "iso.3.6.1.2.1.4.21.1.8.127.0.0.0":"3", "iso.3.6.1.2.1.4.21.1.8.192.168.0.0":"3", "iso.3.6.1.2.1.4.21.1.9.0.0.0.0":"2", "iso.3.6.1.2.1.4.21.1.9.127.0.0.0":"2", "iso.3.6.1.2.1.4.21.1.9.192.168.0.0":"2", "iso.3.6.1.2.1.4.21.1.11.0.0.0.0":"0.0.0.0", "iso.3.6.1.2.1.4.21.1.11.127.0.0.0":"255.0.0.0", "iso.3.6.1.2.1.4.21.1.11.192.168.0.0":"255.255.240.0", "iso.3.6.1.2.1.4.21.1.13.0.0.0.0":".0.0", "iso.3.6.1.2.1.4.21.1.13.127.0.0.0":".0.0", "iso.3.6.1.2.1.4.21.1.13.192.168.0.0":".0.0"}';
*/
sendMetricToDatabase($hostname, $dataToBeInserted, $checkName);

?>
