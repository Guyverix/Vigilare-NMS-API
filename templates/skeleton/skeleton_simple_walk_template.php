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
   $oidMetric                // The array of data to parse in json_encoded format

   OID: OID we walked
   https://oidref.com/OID we walked

   The file name MUST match $oidCheck value so the main script can find this file.
   template_1.3.6.1.4.1.30911.php (example) if we pulled metrics from that specific oid.

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $oidMetric=json_decode($oidMetric, true);

  /* create an empty array of our index values */
  $list=array();

  // When at all possible, make a descrete name for your values.
    foreach ($oidMetric as $k => $v) {
      // echo "VALUE INTERFACE " . $interface . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.OID_TABLE.1.0":
         $v=preg_replace('/\ /', '_', $v);
         $clean[$interface]['DISCRETE_NAME_1']= "$v" ;
         break;
        case "iso.OID_TABLE.2.0":
         $clean[$interface]['DISCRETE_NAME_2']= "$v" ;
         break;
      }
    }
    $mapped[]= $clean[$interface];

  /* Returns mapped[keys] => values for example above */
  //print_r($mapped);
  // exit();

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("DISCRETE_NAME_2", "FILTER_OUT_NAME_45");

  /* The HARDWARE_NAME should be generic for what metrics we are pushing */
  $graphiteRootKey=$hostname . ".HARDWARE_NAME.";

  foreach ($interfaces as $k => $v) {
    /* Match against only values that are numeric and have values to send */
    if ( ! in_array($k, $nonNumericReturns) && !empty($v)) {
      $graphiteKey1=$k;
      $graphiteValue=$v;
      /* At this point we have all data needed to send to Graphite */
      $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
      // echo $graphiteKey." ". $graphiteValue. "\n"; // TESTING VALUES
      $returnArrayValues[$graphiteKey]= $graphiteValue;
    }
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
