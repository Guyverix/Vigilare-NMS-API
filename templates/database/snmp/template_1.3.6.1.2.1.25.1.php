<?php
/*
   this template will grab host system metrics such as uptime for graphite

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

   OID: 1.3.6.1.2.1.25.1
   https://oidref.com/1.3.6.1.2.1.25.1

   The file name MUST match $oidCheck value so the main script can find this file.
   template_1.3.6.1.4.1.30911.php (example) if we pulled metrics from that specific oid.

*/

  if ( empty($hostname)) { $hostname='undefinedInTemplate'; }
  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

  /* create an empty array of our unique values */
  $mapped=array();

  // When at all possible, make a descrete name for your values.
    foreach ($dataToBeInserted as $k => $v) {
      //echo "VALUE KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.2.1.25.1.1.0":
          $mapped["hrSystemUptime"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.2.0":
          $mapped["hrSystemDate"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.3.0":
          $mapped["hrSystemInitialLoadDevice"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.4.0":
          $mapped["hrSystemInitialLoadParameters"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.5.0":
          $mapped["hrSystemNumUsers"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.6.0":
          $mapped["hrSystemProcesses"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.7.0":
          $mapped["hrSystemMaxProcesses"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.100.0":
          $mapped["hrSystemCurrentLoadDevice"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.1.101.0":
          $mapped["hrSystemDefaultPermanentStorageDevice"]= "$v";
          break;
      }
    }

  /* Returns mapped[keys] => values for example above */
  // print_r($mapped);
  // exit();

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("hrSystemInitialLoadParameters", "hrSystemDate","hrSystemUptime");

  /* The HARDWARE_NAME should be generic for what metrics we are pushing */
  $graphiteRootKey=$hostname . ".snmp.general";

  foreach ($mapped as $k => $v) {
    // echo "VALUE KEY " . $k . " VALUE " . $v . "\n";
    /* Match against only values that are numeric and have values to send */
    if ( ! in_array($k, $nonNumericReturns) && !empty($v)) {
      $graphiteKey1=$k;
      $graphiteValue=$v;
      /* At this point we have all data needed to send to Graphite */
      $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
      // echo $graphiteKey. " " . $graphiteValue. "\n"; // TESTING VALUES
      $returnArrayValues[$graphiteKey]= $graphiteValue;
    }
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
