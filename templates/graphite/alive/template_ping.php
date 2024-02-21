<?php
/*
   This skeleton is going to be used to MAKE an include for pollers to consume
   and will send parsed metric data to Graphite.

   this specific file is the default where we dont know ANYTHING.
   We are going to assume a string of data that is close
   to nagios style output.

   From there we will ATTEMPT to make something that Graphite can
   consume..
*/

  // only for testing ( needed if we are not in an object)
  /*
  require __DIR__ . '/../../../src/Infrastructure/Shared/Functions/daemonFunctions.php';

  $hostname = "guyver-office.iwillfearnoevil.com";
  $dataToBeInserted = '["PING OK - Packet loss = 0%, RTA = 0.78 ms|rta=0.777000ms;50.000000;100.000000;0.000000 pl=0%;10;15;0"]';
  $checkName = 'ping';
  $type='alive';
  $cycle = 60;
  */

  // This is manditory for graphite  No periods in hostnames
  $hostname=preg_replace('/\./', '_', $hostname);

  // Initially assume we are working with nagios returns
  // "some random string | metric1=0 metric2=2"
  // "some random string | metric1=0;2;3 metric2=2;4;5"
  // echo "should be array " .  print_r($dataToBeInserted, true); // DEBUG
  $dataToBeInserted = str_replace(['[', ']'],'', $dataToBeInserted);
  // debugger($dataToBeInserted);

  $dataToBeInserted=cleanNrpeMetrics($dataToBeInserted);

  $intCheckName= $checkName;
  if (empty($intCheckName)) { $intCheckName="unknownMonitor"; }
  $intCheckName=preg_replace('/[ .]/','_', $intCheckName); // Names must not have spaces or periods in grapite

  // When at all possible, make a descrete name for your keys.
  foreach ($dataToBeInserted['data'] as $k => $v) {
    // echo "RAW VALUES" . " KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
    $k=rtrim(ltrim($k));                // No whitespace junk in key
    if ( is_null($v)) { $v = ''; }
    $v=rtrim(ltrim($v));                // No whitespace junk in value
    $k=preg_replace('/[ .]/','_', $k);  // Replace spaces and periods with underbar
    $v = preg_replace('/%/','', $v);    // graphite chokes on the % character
    $mapped[$k] = "$v";
  }
  // debugger($mapped);

  /* Returns that are NOT numeric values or we specifically dont care about
     This inspects the $k value, not the $v data
     This is unused in NRPE
  */
  $nonNumericReturns=array("DISCRETE_NAME_2", "FILTER_OUT_NAME_45", "IGNORE_KEY_BLAH");

  /* The type should be generic for what metrics we are pushing based on the poller name */
  $graphiteRootKey=$hostname . "." . $type;

  foreach ($mapped as $k => $v) {
    /* Match against only values that are numeric and have values to send */
    if ( ! in_array($k, $nonNumericReturns) && $v !=='' ) {
      // echo "Not in suppression array and value not empty\n"; //DEBUG
      $graphiteKey1=$k;
      $graphiteValue=$v;
      /* At this point we have all data needed to send to Graphite */
      $graphiteKey=$graphiteRootKey . "." . $intCheckName . "." . $graphiteKey1;
      // echo $graphiteKey." ". $graphiteValue. "\n"; // DEBUG
      $returnArrayValues[$graphiteKey]= $graphiteValue;
    }
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  // debugger($returnArrayValues);
  // exit();
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
