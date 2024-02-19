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

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $oidMetric=json_decode($oidMetric, true);

  /* create an empty array of our index values */
  $list=array();

  /* Retrieve the ethernet index number for every interface
     These are the table returns that are mapped to names.
     This is useful when you get an index value, and need
     discrete returns on a per index set.
     IE: drive 1 statistic data, drive 2 statistic data

     First grab your unique id, and then grab your stats
     specific TO that id.
  */
  foreach ($oidMetric as $k => $v) {
    if ( strpos($k, 'UNIQUE_INDEX') !==false) {
      $interfaceIndexNumber=preg_replace('/UNIQUE_INDEX/','', $k);
      $list[]=$interfaceIndexNumber;
    }
  }
  /* test our array is correct */
  // print_r($list);

  /* Create empty array to fill with mapped values */
  $mapped=array();


  // When at all possible, make a descrete name for your values.
  foreach ($list as $interface) {
    foreach ($oidMetric as $k => $v) {
      // echo "VALUE INTERFACE " . $interface . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.OID_TABLE.1.$interface":
         $v=preg_replace('/\ /', '_', $v);
         $clean[$interface]['DISCRETE_NAME_1']= "$v" ;
         break;
        case "iso.OID_TABLE.2.$interface":
         $clean[$interface]['DISCRETE_NAME_2']= "$v" ;
         break;
      }
    }
    $mapped[]= $clean[$interface];
  }

  /* Returns maped[0-2][keys] => values for example above */
  //print_r($mapped);

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("DISCRETE_NAME_2", "FILTER_OUT_NAME_45");

  /* Now loop through each interface and create our metric key and value pair */
  foreach ($mapped as $interfaces) {
    // $graphiteRootKey=$hostname . ".interfaces." . $interfaces['ifName'];
    $graphiteRootKey=$hostname . ".interfaces." . $interfaces['DISCRETE_NAME_1'];

    foreach ($interfaces as $k => $v) { 
      /* Match against only values that are numeric */
      if ( ! in_array($k, $nonNumericReturns)) {
        $graphiteKey1=$k;
        $graphiteValue=$v;
        /* At this point we have all data needed to send to Graphite */
        $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
        // echo $graphiteRootKey.".".$graphiteKey." ". $graphiteValue. "\n";
        $graphite->testMetric( $graphiteKey, $graphiteValue);
      }
    }
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
