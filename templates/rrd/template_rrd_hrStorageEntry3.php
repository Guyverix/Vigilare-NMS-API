<?php
/*
   This template will parse disk metric values and insert the numerics into
   Rrd for later use.

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Rrd keys
   Example:
   OID: 1.3.6.1.2.1.25.2.3.1 Disk metrics

   https://oidref.com/1.3.6.1.2.1.25.2.3.1

   Only these three values are critical:
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted         // The array of data to parse in json_encoded format

*/

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  // print_r($dataToBeInserted);  // DEBUG
  /* create an empty array of our index values */
  $list=array();

  /*
     Retrieve the index number for every drive
     These are the table returns that are mapped to names.
     This is useful when you get an index value, and need
     discrete returns on a per index set.
     IE: drive 1 statistic data, drive 2 statistic data

     First grab your unique id, and then grab your stats
     specific TO that id.
  */
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '3.6.1.2.1.25.2.3.1.1.') !==false) {
      $driveIndexNumber=preg_replace('/.*.3.6.1.2.1.25.2.3.1.1./','', $k);
      $list[]=$driveIndexNumber;
    }
  }
  /* test our array is correct */
  //  print_r($list);  // DEUBG
  //  exit();  // DEBUG

  /* Create empty array to fill with mapped values */
  $mapped=array();
  $clean=array();


  /*
    CRITICAL TO REMEMBER
    RRDTOOL has a max string lenght of 19 characters for the "name" of the metric
    Always confirm the names defined below are < 19 chars via echo "foo" | wc -c
  */

  // When at all possible, make a descrete name for your values.
  foreach ($list as $drive) {
     //echo "DRIVE= " . $drive . "\n"; // DEUBG
    foreach ($dataToBeInserted as $k => $v) {
       $k = preg_replace('/^.1/','iso', $k);  // 
       //echo "VALUE DRIVE " . $drive . " KEY " . $k . " VALUE " . $v . "\n";  // DEBUG
      switch ($k) {
        case "iso.3.6.1.2.1.25.2.3.1.3.$drive":
          // This is our mount point (name)
          // convert all / to _ and all spaces to - so we can save to a good filename without supidity
          $v=ltrim(rtrim($v));
          $v=preg_replace('/\//', '_', $v);
          $v=preg_replace('/\ /', '-', $v);
          $v=preg_replace('/:/', '_', $v);
          $clean[$drive]['hrStorageDescr'] = "$v";
          break;
        case "iso.3.6.1.2.1.25.2.3.1.1.$drive":
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageIndex", 'value' => "$v", 'type' => 'UNUSED');
          break;
        case "iso.3.6.1.2.1.25.2.3.1.2.$drive":
          $v=preg_replace('/.*.\./', '' , $v);
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageType", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.2.1.25.2.3.1.4.$drive":
          $v =  preg_replace('/\D/', '',$v);
          $v=ltrim(rtrim($v));
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageUnit", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.2.1.25.2.3.1.5.$drive":
          $v =  preg_replace('/\D/', '',$v);
          $v=ltrim(rtrim($v));
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageSize", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.2.1.25.2.3.1.6.$drive":
          $v =  preg_replace('/\D/', '',$v);
          $v=ltrim(rtrim($v));
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageUsed", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.2.1.25.2.3.1.7.$drive":
          $clean[$drive]['dataToBeInserted'][] = array( 'name' => "hrStorageFailure", 'value' => "$v", 'type' => 'GAUGE');
          break;
      }
    }
    $mapped[]= $clean[$drive];
  }

  /* Returns maped[#-##][keys] => values for example above */
  // print_r($mapped); // DEUBG
  // exit(); // DEUBG

  /* Returns that are NOT numeric values or we specifically dont care about */
  $window = ($cycle * 3);

  // https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html
  $validTypes = array("COUNTER", "GAUGE", "DCOUNTER", "DERIVE", "DDERIVE", "ABSOLUTE", "COMPUTE");

  /* Now loop through each drive and create our metric key and value pair */
  foreach ($mapped as $drives) {
    // print_r($drives); // DEBUG
    $rrdRootFile=$hostname . "/snmp/drive/space/" . $drives['hrStorageDescr'] . "_32.rrd";  // really int values, but JIC I find some good 64 counters dont want to recode

    $rrdReturnData[$drives['hrStorageDescr']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';

    // go through each metricName and add it into create and update array for each drive
    foreach ($drives['dataToBeInserted'] as $dataToAdd) {
      // print_r($dataToAdd); // DEBUG
      if ( in_array( $dataToAdd['type'], $validTypes) ) {
        $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
        $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
      }
    } // end foreach
    // returnDataUpdate is REALLY sensitive.  Make sure it is 100% clean
    // $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);  // CSH testing if this even needed
    $rrdReturnData[$drives['hrStorageDescr']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$drives['hrStorageDescr']]['update']=$rrdReturnDataUpdate;
  } // end foreach

  // This is called from sendMetricToRrd.php which has the Rrd class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$rrdReturnData;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;
?>
