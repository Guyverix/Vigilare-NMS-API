<?php
  /*
    Parse standard printer MIB for printer stats.  Only tested on Brother currently.
    Brother does not have an oid for percentage left, more of toner, no-toner values
    https://oidref.com/1.3.6.1.2.1.43.11.1.1
  */



  if ( ! is_array($dataToBeInserted)) {  // For safety, it should always be json inbound, but sometimes people (me) screw up.
    $dataToBeInserted=json_decode($dataToBeInserted, true);
  }
  // print_r($dataToBeInserted);  // DEBUG
  // exit();
  $window = ($cycle * 2);


  // https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
  if (! $dataToBeInserted === []) {
    foreach($dataToBeInserted as $createKv) {  // Make k => v pairs from oid
      $res     = explode(' : ',$createKv);
      $key01   = ltrim(rtrim($res[0]));
      $value01 = ltrim(rtrim($res[1]));
      $newKv[$key01] = $value01;
    }
    // print_r($newKv);
    $dataToBeInserted = $newKv;  // overwrite initial raw array
  }

  /* create an empty array of our index values */
  $list=array();

  /*
    OID at least by Brother is not returning an index, we have to count by hand
    https://stackoverflow.com/questions/5945199/counting-occurrence-of-specific-value-in-an-array-with-php
  */
  $indexCount = 0;
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '3.6.1.2.1.43.11.1.1.2.1') !== false) {
      $indexCount ++;
      $indexArray[] = $indexCount;  // Silly, but this way we match how other templates count
    }
  }
  //print_r($dataToBeInserted);
  // echo "INDEX COUNT " . $indexCount . "\n";

  /* Create empty array to fill with mapped values */
  $mapped=array();
  $clean=array();  // Values based off of 3.6.1.2.1.43.11
  $clean2=array();  // Values based off of 3.6.1.2.1.43.12

  /*
    CRITICAL TO REMEMBER
    RRDTOOL has a max string lenght of 19 characters for the "name" of the metric
    Always confirm the names defined below are < 19 chars via echo "foo" | wc -c
  */

  //  print_r($dataToBeInserted);
  //  print_r($indexArray);
  //  exit();
  foreach ($indexArray as $indexValue) {
    foreach ($dataToBeInserted as $k => $v) {   // Assumes we are setup as array[#] oid => value
      // echo "KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
      $substringLength = strlen($k);                                         // Validation so 1 does not match 10 on the indexes
      $sLength = strlen("iso.3.6.1.2.1.43.11.1.1.6.1.$indexValue");           // hacky validation so 1 does not match 10+ on the indexes
      switch ($k) {
      case strpos($k , "iso.3.6.1.2.1.43.11.1.1.6.1.$indexValue") !== false && $sLength == $substringLength :
        $v=trim($v);
        $v=preg_replace('/\ /', '_', $v); // change spaces to _
        $v=preg_replace('/"/','', $v);  // Strip quotes
        $clean[$indexValue]['Filter'] = $v;                                                                                // Description
        break;
      case strpos($k, "iso.3.6.1.2.1.43.11.1.1.7.1.$indexValue") !== false  && $sLength == $substringLength:
        $v=preg_replace('/"/','', $v);  // Strip quotes
        $v=rtrim(ltrim($v));
        $clean[$indexValue]['dataToBeInserted'][] = array('name' => 'prtSuppliesEntry', 'value' => $v, 'type' => 'GAUGE');     // Guessing type of recepticle
        break;
      case strpos($k, "iso.3.6.1.2.1.43.11.1.1.8.1.$indexValue") !== false && $sLength == $substringLength:
        $v=preg_replace('/"/','', $v);  // Strip quotes
        $v=rtrim(ltrim($v));
        $clean[$indexValue]['dataToBeInserted'][] = array('name' => 'prtMaxCapacity', 'value' => $v, 'type' => 'GAUGE');      // estimated number of prints from new state
        break;
      case strpos($k, "iso.3.6.1.2.1.43.11.1.1.9.1.$indexValue") !== false && $sLength == $substringLength:
        $v=preg_replace('/"/','', $v);  // Strip quotes
        $v=rtrim(ltrim($v));
        $clean[$indexValue]['dataToBeInserted'][] = array('name' => 'prtSuppliesLevel', 'value' => $v, 'type' => 'GAUGE');    // number of estimated prints remaining
        break;
      }  // end switch
    }  // end k v
  }  // end indexArray


  $secondListStart = $indexCount + 1 ;  // Increment to the next index for alarm integers
  //echo "New INDEX " . $secondListStart . "\n";
  $clean[$secondListStart]['Filter'] = "alarms";

  foreach ($dataToBeInserted as $k => $v) {   // Assumes we are setup as array[#] oid => value
    //echo "KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
    switch ($k) {
    case strpos($k , "iso.3.6.1.2.1.43.18.1.1.2.1.1") !== false:
      $v=preg_replace('/"/','', $v);  // Strip quotes
      $clean[$secondListStart]['dataToBeInserted'][] = array( 'name' => 'prtAlertSevLevel', 'value' => $v, 'type' => 'GAUGE');
      break;
    case strpos($k , "iso.3.6.1.2.1.43.18.1.1.4.1.1") !== false:
      $v=preg_replace('/"/','', $v);  // Strip quotes
      $clean[$secondListStart]['dataToBeInserted'][] = array( 'name' => 'prtAlertGroup', 'value' => $v, 'type' => 'GAUGE');
      break;
    case strpos($k , "iso.3.6.1.2.1.43.18.1.1.7.1.1") !== false:
      $v=preg_replace('/"/','', $v);  // Strip quotes
      $clean[$secondListStart]['dataToBeInserted'][] = array( 'name' => 'prtAlertCode', 'value' => $v, 'type' => 'GAUGE');
      break;
    } // end switch
  } // end foreach
  // echo "CLEAN " . print_r($clean) . "\n";
  // exit();

  // https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html
  $validTypes = array("COUNTER", "GAUGE", "DCOUNTER", "DERIVE", "DDERIVE", "ABSOLUTE", "COMPUTE");

  /* Now loop through each thing and create our metric key and value pair */
  foreach ($clean as $someNames) {
    // echo "SOMENAMES " . json_encode($someNames,1) . "\n"; // DEUBG
    $rrdRootFile = $hostname . "/snmp/brotherPrinter/" . $someNames['Filter'] . "_32.rrd";  // try for consistencey..  dont really need _32, but meh
    $rrdReturnData[$someNames['Filter']]['fileName'] = $rrdRootFile;
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

    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);
    $rrdReturnData[$someNames['Filter']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$someNames['Filter']]['update']=$rrdReturnDataUpdate;
  } // end foreach
  // print_r($rrdReturnData);   // DEBUG
  // exit();

  // This is called from sendMetricToRrd.php which has the Rrd class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$rrdReturnData;
  //  print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;
?>
