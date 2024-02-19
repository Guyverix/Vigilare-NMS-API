<?php
  /*
     This template will parse something for values and insert the numerics into
     Rrd for later use.

     Details that must be sent to file: hostname, metric array for parsing
     All templates are standalone, as we need to make readable and sane Rrd keys
     Example:
     OID: 1.3.6.1.4.1.2021.4

     https://oidref.com/ <-- use me

     Only these three values are critical:
     $hostname                 // fqdn or IP of host
     $dataToBeInserted         // The array of data to parse in json_encoded format
     $cycle                    // What the cycle is of the check.  RRD needs this inforamtion

     Vars available for additional tweaks:
     $checkName                // a human readable generic name for a given check
     $checkAction              // generally an snmp oid value for the check where we walked something

     Return back to caller
     $this->returnArrayValues  // our array return for the object

  */

  /*
    SKEL: CHANGE THESE SKEL VALUES AS A STARTING POINT

    OIDINDEX: usually a numberic list at the beginning of the table return
    $SOMENAME: Usually an identifier in the table that is unique like sda1 or enp2s0
    $someNames: Internal name when creating the array to return data.  Recommend discriptive name.  Your choice
    OIDTABLE: The OID table we are parsing against
    NAME#: A unique name for that leaf of the oid returned
    UNIQUE_METRIC_NAME: Ties into $SOMENAME.  This must be unique per rrd file
  */


  if ( ! isset($logger)) {
    global $logger;
  }

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  // print_r($dataToBeInserted);  // DEBUG
  /* create an empty array of our index values */
  $list=array();

  /*
     Retrieve the index number for every "thing"
     These are the table returns that are mapped to names.
     This is useful when you get an index value, and need
     discrete returns on a per index set.
     IE: "thing" 1 statistic data, "thing" 2 statistic data

     First grab your unique id, and then grab your stats
     specific TO that id.
  */

  /*
    This is a 0 index array.  No need to loop
  */
  /*
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, 'OIDINDEX.') !==false) {
      $SOMENAMEIndexNumber=preg_replace('/.*.OIDINDEX./','', $k);
      $list[]=$SOMENAMEIndexNumber;
    }
  }
  */
  $list[] = 0;
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
  foreach ($list as $SOMENAME) {
    // echo "THING= " . $SOMENAME . "\n"; // DEUBG
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE THING " . $SOMENAME . " KEY " . $k . " VALUE " . $v . "\n";  // DEBUG
      switch ($k) {
        case "iso.3.6.1.4.1.2021.4.2.$SOMENAME":
          // This is our metric name
          // convert all / to _ and all spaces to - so we can save to a good filename without supidity
          $v=ltrim(rtrim($v));                // Remove whitespace
          $v=preg_replace('/\//', '_', $v);   // Convert / to _
          $v=preg_replace('/\ /', '-', $v);   // Convert space to -
          $clean[$SOMENAME]['UNIQUE_METRIC_NAME'] = "memory";
          break;
        case "iso.3.6.1.4.1.2021.4.3.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalSwap", 'value' => "$v", 'type' => 'GAUGE');  // So we know what the table is returning, but we are not saving the data as a metric
          break;
        case "iso.3.6.1.4.1.2021.4.4.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $v=preg_replace('/.*.\./', '' , $v);
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memAvailSwap", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.5.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalReal", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.6.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memAvailReal", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.11.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalFree", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.12.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memMinimumSwap", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.13.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memShared", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.14.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memBuffer", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.15.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memCached", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.4.18.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalSwapX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.19.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memAvailSwapX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.20.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalRealX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.21.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memAvailRealX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.22.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memTotalFreeX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.23.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memMinimumSwapX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.24.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memSharedX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.25.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memBufferX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.26.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memCachedX", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.4.100.$SOMENAME":
          if ( $v == 'noError' ) { $v = 0; } else { $v = 1; }
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "memError", 'value' => "$v", 'type' => 'GAUGE');
          break;
      }
    }
    $mapped[]= $clean[$SOMENAME];
  }

  /* Returns maped[#-##][keys] => values for example above */
  // print_r($mapped); // DEUBG
  // exit(); // DEUBG

  /* Returns that are NOT numeric values or we specifically dont care about */
  $window = ($cycle * 3);

  // https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html
  $validTypes = array("COUNTER", "GAUGE", "DCOUNTER", "DERIVE", "DDERIVE", "ABSOLUTE", "COMPUTE");

  /* Now loop through each "thing" and create our metric key and value pair */
  foreach ($mapped as $someNames) {
    // print_r($someNames); // DEBUG
    $rrdRootFile=$hostname . "/snmp/memory/" . $someNames['UNIQUE_METRIC_NAME'] . "_64.rrd";  // really int values, but JIC I find some good 64 counters dont want to recode

    $rrdReturnData[$someNames['UNIQUE_METRIC_NAME']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';

    // go through each metricName and add it into create and update array for each thing
    foreach ($someNames['dataToBeInserted'] as $dataToAdd) {
      // print_r($dataToAdd); // DEBUG
      if ( in_array( $dataToAdd['type'], $validTypes) ) {
        $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
        $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
      }
    } // end foreach
    // returnDataUpdate is REALLY sensitive.  Make sure it is 100% clean
    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);
    $rrdReturnData[$someNames['UNIQUE_METRIC_NAME']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$someNames['UNIQUE_METRIC_NAME']]['update']=$rrdReturnDataUpdate;
  } // end foreach

  // This is called from sendMetricToRrd.php which has the Rrd class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $logger->debug("template_rrd_memory.php rrdReturnData: " . json_encode($rrdReturnData,1));
  $this->returnArrayValues=$rrdReturnData;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;
?>
