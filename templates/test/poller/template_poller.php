<?php
/*
  General attempt to save Poller metrics for iteration cycles
  this should not need too much err correction, as it is all internal
  variables that are set at runtime.

  This is specific to Graphite metric saves.  RRD will choke on this
*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

  // print_r($dataToBeInserted); // DEBUG

  // When at all possible, make a descrete name for your keys.
  foreach ($dataToBeInserted as $k => $v) {
    // echo "RAW VALUES" . " KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
    $k=rtrim(ltrim($k));                // No whitespace junk in key
    $v=rtrim(ltrim($v));                // No whitespace junk in value
    $k=preg_replace('/[ .]/','_', $k);  // Replace spaces and periods with underbar
    $mapped[$k] = "$v";
  }
  /* Returns mapped[keys] => values for example above */
  // print_r($mapped); // DEBUG
  // exit(); // DEBUG

  /* Returns that are NOT numeric values or we specifically dont care about
     This inspects the $k value, not the $v data
     This is unused in NRPE
  */

  /* The HARDWARE_NAME should be generic for what metrics we are pushing */
  $graphiteRootKey=$hostname . ".poller";

  foreach ($mapped as $k => $v) {
    $graphiteKey1=$k;
    $graphiteValue=$v;
    /* At this point we have all data needed to send to Graphite */
    $graphiteKey=$graphiteRootKey . "." . $checkName . "." . $graphiteKey1;
    // echo $graphiteKey." ". $graphiteValue. "\n"; // DEBUG
    $returnArrayValues[$graphiteKey]= $graphiteValue;
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
