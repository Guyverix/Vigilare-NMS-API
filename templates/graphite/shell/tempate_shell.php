<?php
/*
  This is a catchall template of last resort.
*/

  $hostname=preg_replace('/\./', '_', $hostname);
  // echo "Intial insert stuff " . $dataToBeInserted; // DEBUG

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  if (! is_array($dataToBeInserted)) {
    $dataToBeInserted=json_decode($dataToBeInserted, true);
  }
  // echo "should be array " .  print_r($dataToBeInserted, true); // DEBUG

  // cleanNrpeMetrics will be our filter to work against for attempting to get values
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
  $graphiteRootKey=$hostname . ".shell";

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
