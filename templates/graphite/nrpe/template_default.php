<?php
/*
   This skeleton is going to be used to MAKE an include for pollers to consume
   and will send parsed metric data to Graphite.

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys
   Example:
   OID: 1.3.6.1.2.1.31.1.1.1 ethernet 64bit counters
   OID: 1.3.6.1.4.1.2021.4   memory statistics
   OID: 1.3.6.1.4.1.2021.11  CPU
   OID: 1.3.6.1.4.1.2021.10  Load

   Only these three values are critical
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

   OID: OID we walked
   https://oidref.com/OID we walked

   The file name MUST match $oidCheck value so the main script can find this file.
   template_1.3.6.1.4.1.30911.php (example) if we pulled metrics from that specific oid.

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  // echo "Intial insert stuff " . $dataToBeInserted; // DEBUG

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  if (! is_array($dataToBeInserted)) {
    $dataToBeInserted=json_decode($dataToBeInserted, true);
  }
  // echo "should be array " .  print_r($dataToBeInserted, true); // DEBUG

  $dataToBeInserted=cleanNrpeMetrics($dataToBeInserted[0]);
  // echo "Should be string " .  print_r($dataToBeInserted, true); // DEBUG

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
    $mapped[$k] = "$v";
  }
  /* Returns mapped[keys] => values for example above */
   // print_r($mapped); // DEBUG
   // exit(); // DEBUG

  /* Returns that are NOT numeric values or we specifically dont care about
     This inspects the $k value, not the $v data
     This is unused in NRPE
  */
  $nonNumericReturns=array("DISCRETE_NAME_2", "FILTER_OUT_NAME_45", "IGNORE_KEY_BLAH");

  /* The HARDWARE_NAME should be generic for what metrics we are pushing */
  $graphiteRootKey=$hostname . ".nrpe";

  foreach ($mapped as $k => $v) {
    /* Match against only values that are numeric and have values to send */
//    if ( ! in_array($k, $nonNumericReturns) && ! empty($v)) {
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
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
