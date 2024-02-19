<?php
/*
  Generic RRD saver for the Poller performance.
  Any new dataoutput is going to require a regeneration of the RRD
  as the database in the rrd does not seem to like adding new
  random values.
*/

  if ( empty($dataToBeInserted)) {
    $dataToBeInserted = "";
  }
  $dataToBeInserted = json_decode($dataToBeInserted, true);

  // Set our RRD window for RRD creates

  // Calls the daemon functions for cleanNrpeMetrics
  //$dataToBeInserted=cleanNrpeMetrics($dataToBeInserted[0]);
  // print_r($dataToBeInserted); // DEBUG

  // Poller needs to adjust directory better
  $checkNameFull=explode('-',$checkName);
  $checkNameFront=$checkNameFull[0];
  $checkNameBack=$checkNameFull[1];
  $cycle=$checkNameBack;
  $window = ($checkNameBack * 2);

  /*
    DA RULEZ!  If there is no parsable k => v pair, there is no metric!
    Initially will just look for numbers in the values, and continue adding
    rules from there
  */
  $mapped=array();
  foreach ($dataToBeInserted as $k => $v) {
    // echo "RAW VALUES" . " KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
    // This is likely going to have heartburn at times.  19 char max!
    $k=rtrim(ltrim($k));                // No whitespace junk in key
    $k=preg_replace('/[ \/.]/','_', $k);  // Replace spaces and periods with underbar
    // This is going to be critical here:
    $v=preg_replace('/[^\d,.]+/','', $v); // Only numbers allowed!
    $v=rtrim(ltrim($v));                // No whitespace junk in value
    if (is_numeric($v)) {
      // echo "Key " . $k . " has a value of " . $v . " which is considered numeric\n"; // DEBUG
      $mapped[$checkName]['dataToBeInserted'][] = array('name' => $k, 'value' => $v, 'type' => 'GAUGE');
    }
    else {
      echo "Key " . $k . " has a value of " . $v . " which is NOT considered numeric\n"; // DEBUG
    }
  } // end foreach

  // print_r($mapped); // DEBUG
  // exit(); // DEBUG


  $rrdReturnData=array();
  /* Now loop through each interface and define create and update values */
  foreach ($mapped as $pairs) {
    $rrdRootFile = $hostname . "/poller/" . $checkNameFront . "/" . $checkName . ".rrd";
    // echo "FILENAME :" . $rrdRootFile . "\n";  // DEBUG

    $rrdReturnData[$checkName]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';
    // go through each name and add it into create and update array for each interface
    foreach ($pairs['dataToBeInserted'] as $dataToAdd) {
      $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
      $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
    } // end foreach
    $rrdReturnData[$checkName]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$checkName]['update']=$rrdReturnDataUpdate;
  } // end foreach


  // This is called from sendMetricToRrd.php which has the Rrd class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$rrdReturnData;
  return $this->returnArrayValues;
?>
