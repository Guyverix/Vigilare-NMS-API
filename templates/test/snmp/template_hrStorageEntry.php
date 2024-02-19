<?php
if ( ! isset($logger)) {
  global $logger;
}


/*
   This template will parse disk metric values and insert the numerics into
   Graphite for later use.

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys
   Example:
   OID: 1.3.6.1.2.1.25.2.3.1 Disk metrics

   https://oidref.com/1.3.6.1.2.1.25.2.3.1

   Only these three values are critical:
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

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
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '3.6.1.2.1.25.2.3.1.1.') !==false) {
      $interfaceIndexNumber=preg_replace('/.*.3.6.1.2.1.25.2.3.1.1./','', $k);
      $list[]=$interfaceIndexNumber;
    }
  }
  /* test our array is correct */
  //  print_r($list);
  //  exit();

  /* Create empty array to fill with mapped values */
  $mapped=array();
  $clean=array();

  // When at all possible, make a descrete name for your values.
  foreach ($list as $interface) {
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE " . $interface . " KEY " . $k . " VALUE " . $v . "\n";  # useful for debugging
      switch ($k) {
        case "iso.3.6.1.2.1.25.2.3.1.1.$interface":
          $clean[$interface]["hrStorageIndex"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.2.$interface":
          $clean[$interface]["hrStorageType"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.3.$interface":
          // convert all / to _ and all spaces to - so we can call URLs without a bunch of
          // headaches.  Convert period to spaces as well.  Ouch!
          $v=preg_replace('/\./', '-', $v);
          $v=preg_replace('/\//', '_', $v);
          $v=preg_replace('/\ /', '-', $v);
          $clean[$interface]["hrStorageDescr"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.4.$interface":
          $v = preg_replace('/[a-zA-Z ]/i','', $v);  // leave only numbers dammit
          $clean[$interface]["hrStorageAllocationUnits"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.5.$interface":
          $clean[$interface]["hrStorageSize"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.6.$interface":
          $clean[$interface]["hrStorageUsed"]= "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.7.$interface":
          $clean[$interface]["hrStorageAllocationFailures"]= "$v";
          break;
      }
    }
    $mapped[]= $clean[$interface];
  }
  // Now that we have data, we need to manipuate it before forwarding on
  // raw hrStorageSize and used need to be multiplied by hrStorageAllocationUnits
  $mapped2=array();

  foreach ($mapped as $filter) {
  //  $logger->debug("function debugging array_key mapped " . array_keys($mapped));
//    $logger->debug("function debugging filter " . json_encode($filter,1));
    if (empty($filter['hrStorageSize'])) { $filter['hrStorageSize'] = 0; }
    if (empty($filter['hrStorageUsed'])) { $filter['hrStorageUsed'] = 0; }
    $origSize = trim($filter['hrStorageSize']);
    $origUsed = trim($filter['hrStorageUsed']);
    $origUnit = trim($filter['hrStorageAllocationUnits']);
    $adjustSize = $origSize * $origUnit;
    $adjustUsed = $origUsed * $origUnit;
    $filter['hrStorageUsed'] = $adjustUsed;
    $filter['hrStorageSize'] = $adjustSize;
    //$logger->debug("function debugging math orig " . $origSize . " post " . $adjustSize);
    $mapped2[] = $filter;
  }
  /* Returns maped[#-##][keys] => values for example above */
  // print_r($mapped,true);
  // exit();
  //$logger->debug("function debugging mapped POST" . json_encode($mapped,1));
  $logger->debug("function debugging mapped2 " . $hostname .' ' . json_encode($mapped2,1));
  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("hrStorageDescr", "hrStorageIndex", "hrStorageType");

  /* Now loop through each interface and create our metric key and value pair */
  foreach ($mapped2 as $interfaces) {
    // $graphiteRootKey=$hostname . ".interfaces." . $interfaces['ifName'];
    $graphiteRootKey=$hostname . ".snmp.hrStorageEntry." . $interfaces['hrStorageDescr'];

    foreach ($interfaces as $k => $v) {
      /* Match against only values that are numeric */
      if ( ! in_array($k, $nonNumericReturns)) {
        $graphiteKey1=$k;
        $graphiteValue=$v;
        /* At this point we have all data needed to send to Graphite */
        // Make damn sure we do not have " or ' characters inside our index (graphiteKey)!
        $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
        $graphiteKey=preg_replace('/"/', '', $graphiteKey);
        $graphiteKey=preg_replace("/'/", "", $graphiteKey);
        // echo $graphiteKey." ". $graphiteValue. "\n"; // TESTING VALUES
        $returnArrayValues[$graphiteKey]= $graphiteValue;
      }
    }
  }
  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
