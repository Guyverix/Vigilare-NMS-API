<?php
/*
   The file name MUST match $checkName value so the main script can find this file.
   OID: 1.3.6.1.4.1.2021.13.16  lm-sensors

   This is actually going to parse 3 different sets of tables
   https://oidref.com/1.3.6.1.4.1.2021.13.16.1
   https://mibs.observium.org/mib/LM-SENSORS-MIB/
   There IS an optional 4th table, but I have never seen it used
*/

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  /*
     This is sent in a messy format.  We need to make
     a good K => V pairing before we parse this
  */
/*
  $cleanup=array();
  foreach ($dataToBeInserted as $singleData) {
    $singleData=explode(':',$singleData);
    $singleData[0] = ltrim(rtrim($singleData[0]));
    $singleData[1] = ltrim(rtrim($singleData[1]));
    // echo "KEY " . $singleData[0] . " VALUE " . $singleData[1] . "\n";
    $cleanUp[$singleData[0]] = $singleData[1];
  }
  // print_r($cleanUp);
  // exit();
*/
  $cleanUp=$dataToBeInserted;
  /* create an empty array of our index values */
  $window = ($cycle * 3);
  $list=array();
  $mapped=array();
  $clean=array();

  foreach ($cleanUp as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.16.2.1.1.') !==false) {
      $tempIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.13.16.2.1.1./','', $k);
      $list[]=$tempIndexNumber;
    } // end if
  }  // end foreach

  /* test our array is correct */
  // print_r($list); // TESTING VALUES
  // exit(); // TESTING VALUES

  // When at all possible, make a descrete name for your values.
  foreach ($list as $temp) {
    foreach ($cleanUp as $k => $v) {
      // echo "VALUE TEMP " . $temp . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case strpos($k,"16.2.1.2.$temp" ) !== false:
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/\:/', '_', $v); // change colon to _
         $v=preg_replace('/\./', '_', $v); // change period to _
         $v=preg_replace('/"/','', $v);    // Strip quotes
         if ($v[0] === "_") { $v = substr($v, 1); } // Strip leading _ off of name
         $clean[$temp]['Name']= "temp.$v" ;
         break;
        case strpos($k,"16.2.1.3.$temp" ) !== false:
         // Many manufacturers give a large value and we have to / 1000 to get real values
         $v=$v / 1000;
         $v=rtrim(ltrim($v));
         $clean[$temp]['dataToBeInserted'][]= array( 'name' => 'temp', 'value'=> $v, 'type' => 'GAUGE') ;
         break;
      }  // end switch
    } // end foreach
    // This cleans up our array and makes it so we have reliabe indexes to foreach through
    $mapped[]= $clean[$temp];
  } // end foreach

  /* Returns mapped[#-###][keys] => values for example above */
  // print_r($mapped); // TESTING VALUES
  // exit(); // TESTING VALUES

  /* Create empty array to fill with mapped values */
  $mapped2=array();
  $clean2=array();
  $list2=array();
  // Second set of tables
  foreach ($cleanUp as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.16.3.1.1.') !==false) {
      $fanIndexNumber= $v;
      $list2[]=$fanIndexNumber;
    } // end if
  }  // end foreach
  // print_r($list2); // TESTING VALUES

  // When at all possible, make a descrete name for your values.
  foreach ($list2 as $fan) {
    $fan=ltrim(rtrim($fan));
    foreach ($cleanUp as $k => $v) {
      // echo "VALUE INTERFACE FAN " . $fan . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case strpos($k ,"13.16.3.1.2.$fan") !==false:
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/"/','', $v);  // Strip quotes
         $v=preg_replace('/\./', '_', $v); // change period to _
         if ($v[0] === "_") { $v = substr($v, 1); } // Strip leading _ off of name
         $clean2[$fan]['Name']= "fan.$v" ;
         break;
        case strpos($k ,"13.16.3.1.3.$fan") !== false:
         $v=rtrim(ltrim($v));
         $clean2[$fan]['dataToBeInserted'][]= array( 'name' => 'fan', 'value'=> $v, 'type' => 'GAUGE') ;
         break;
      }  // end switch
    } // end foreach
    // This cleans up our array and makes it so we have reliabe indexes to foreach through
    $mapped2[]= $clean2[$fan];
  } // end foreach

  /* Returns mapped[#-###][keys] => values for example above */
   // print_r($mapped2); // TESTING VALUES
   // exit(); // TESTING VALUES

  /* Create empty array to fill with mapped values */
  $mapped3=array();
  $clean3=array();
  $list3=array();
  // Second set of tables
  foreach ($cleanUp as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.16.4.1.1.') !==false) {
      $voltIndexNumber= $v;
      $list3[]=$voltIndexNumber;
    } // end if
  }  // end foreach
  // print_r($list3); // TESTING VALUES

  // When at all possible, make a descrete name for your values.
  foreach ($list3 as $volt) {
    $volt=ltrim(rtrim($volt));
    foreach ($cleanUp as $k => $v) {
      // echo "VALUE INTERFACE FAN " . $fan . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case strpos($k ,"13.16.4.1.2.$volt") !==false:
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/"/','', $v);  // Strip quotes
         $v=preg_replace('/\+/','plus', $v);  // change + to string
         $v=preg_replace('/\-/','minus', $v);  // change - to string
         $v=preg_replace('/\./', '_', $v); // change period to _
         if ($v[0] === "_") { $v = substr($v, 1); } // Strip leading _ off of name
         $clean3[$volt]['Name']= "volt.$v" ;
         break;
        case strpos($k, "13.16.4.1.3.$volt") !== false:
         $v=rtrim(ltrim($v));
         // Many manufacturers give a large value and we have to / 1000 to get real values
         $v=$v / 1000;
         $clean3[$volt]['dataToBeInserted'][]= array( 'name' => 'voltage', 'value'=> $v, 'type' => 'GAUGE') ;
         break;
      }  // end switch
    } // end foreach
    // This cleans up our array and makes it so we have reliabe indexes to foreach through
    $mapped3[]= $clean3[$volt];
  } // end foreach
  //  print_r($mapped3);
  //  exit();

  // Merge all three tables together
  $complete=array_merge($mapped, $mapped2);
  $complete=array_merge($complete, $mapped3);
  // print_r($complete);
  //  exit();

  // https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html
  $validTypes = array("COUNTER", "GAUGE", "DCOUNTER", "DERIVE", "DDERIVE", "ABSOLUTE", "COMPUTE");

  /* Now loop through each thing and create our metric key and value pair */
  foreach ($complete as $someNames) {
    // print_r($someNames); // DEBUG
    $rrdRootFile=$hostname . "/snmp/lm-sensors/" . $someNames['Name'] . "_32.rrd";  // really int values, but JIC I find some good 64 counters dont want to recode

    $rrdReturnData[$someNames['Name']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';

    // go through each metricName and add it into create and update array for each drive
    foreach ($someNames['dataToBeInserted'] as $dataToAdd) {
      // print_r($dataToAdd); // DEBUG
      if ( in_array( $dataToAdd['type'], $validTypes) ) {
        $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
        $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
      }
    } // end foreach
    // returnDataUpdate is REALLY sensitive.  Make sure it is 100% clean
    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);
    $rrdReturnData[$someNames['Name']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$someNames['Name']]['update']=$rrdReturnDataUpdate;
  } // end foreach


  //print_r($rrdReturnData);
  // This is called from sendMetricToRrd.php which has the Rrd class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$rrdReturnData;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

?>
