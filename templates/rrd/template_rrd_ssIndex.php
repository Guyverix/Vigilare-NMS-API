<?php
  /*
     This template will parse something for values and insert the numerics into
     Rrd for later use.

     Details that must be sent to file: hostname, metric array for parsing
     All templates are standalone, as we need to make readable and sane Rrd keys
     Example:
     OID: 1.3.6.1.4.1.2021.11

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
    This is not needed when there is only a single index
  */
  /*
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '3.6.1.4.1.2021.11.') !==false) {
      $SOMENAMEIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.11./','', $k);
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
        case "iso.3.6.1.4.1.2021.11.2.$SOMENAME":
          // This is our metric name
          // convert all / to _ and all spaces to - so we can save to a good filename without supidity
          $v=ltrim(rtrim($v));                // Remove whitespace
          $v=preg_replace('/\//', '_', $v);   // Convert / to _
          $v=preg_replace('/\ /', '-', $v);   // Convert space to -
          $clean[$SOMENAME]['UNIQUE_METRIC_NAME'] = "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.3.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssSwapIn", 'value' => "$v", 'type' => 'GAUGE');  // So we know what the table is returning, but we are not saving the data as a metric
          break;
        case "iso.3.6.1.4.1.2021.11.4.$SOMENAME":
          $v=preg_replace('/.*.\./', '' , $v);
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssSwapOut", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.5.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssIOSent", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.6.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssIOReceive", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.7.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssSysInterrupts", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.8.$SOMENAME":
          $v =  preg_replace('/\D/', '',$v); // Strip everything but numbers
          $v=ltrim(rtrim($v));               // strip whitespace
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssSysContext", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.9.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuUser", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.10.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuSystem", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.11.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuIdle", 'value' => "$v", 'type' => 'GAUGE');
          break;
        case "iso.3.6.1.4.1.2021.11.50.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawUser", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.51.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawNice", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.52.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawSystem", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.53.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawIdle", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.54.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawWait", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.55.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawKernel", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.56.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawInterrupt", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.57.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssIORawSent", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.58.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssIORawReceived", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.59.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssRawInterrupts", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.60.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssRawContexts", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.61.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawSoftIRQ", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.62.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssRawSwapIn", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.63.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssRawSwapOut", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.64.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawSteal", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.65.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawGuest", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.66.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuRawGuestNice", 'value' => "$v", 'type' => 'COUNTER');
          break;
        case "iso.3.6.1.4.1.2021.11.67.$SOMENAME":
          $clean[$SOMENAME]['dataToBeInserted'][] = array( 'name' => "ssCpuNumCpus", 'value' => "$v", 'type' => 'GAUGE');
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
    $rrdRootFile=$hostname . "/snmp/cpu/statistics/" . $someNames['UNIQUE_METRIC_NAME'] . "_32.rrd";  // really int values, but JIC I find some good 64 counters dont want to recode

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
  $this->returnArrayValues=$rrdReturnData;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;
?>
