<?php
/*
   Only these three values are critical
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

   The file name MUST match $oidCheck value so the main script can find this file.
   OID: 1.3.6.1.4.1.2021.13.16  lm-sensors

   This is actually going to parse 3 different sets of tables
   https://oidref.com/1.3.6.1.4.1.2021.13.16.1
   https://mibs.observium.org/mib/LM-SENSORS-MIB/
   There IS an optional 4th table, but I have never seen it used
*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

  /* create an empty array of our index values */
  $list=array();
  $mapped=array();
  $clean=array();

  foreach ($dataToBeInserted as $k => $v) {
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
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE " . $temp . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.4.1.2021.13.16.2.1.2.$temp":
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/\:/', '_', $v); // change colon to _
         $v=preg_replace('/\./', '_', $v); // change period to _
         $v=preg_replace('/"/','', $v);    // Strip quotes
         $clean[$temp]['Name']= "temp.$v" ;
         break;
        case "iso.3.6.1.4.1.2021.13.16.2.1.3.$temp":
         $clean[$temp]['Value']= "$v" ;
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
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.16.3.1.1.') !==false) {
      $fanIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.13.16.3.1.1./','', $k);
      $list2[]=$fanIndexNumber;
    } // end if
  }  // end foreach
  // print_r($list2); // TESTING VALUES

  // When at all possible, make a descrete name for your values.
  foreach ($list2 as $fan) {
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE FAN " . $fan . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.4.1.2021.13.16.3.1.2.$fan":
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/"/','', $v);  // Strip quotes
         $v=preg_replace('/\./', '_', $v); // change period to _
         $clean2[$fan]['Name']= "fan.$v" ;
         break;
        case "iso.3.6.1.4.1.2021.13.16.3.1.3.$fan":
         $clean2[$fan]['Value']= "$v" ;
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
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.16.4.1.1.') !==false) {
      $voltIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.13.16.4.1.1./','', $k);
      $list3[]=$voltIndexNumber;
    } // end if
  }  // end foreach
  // print_r($list3); // TESTING VALUES

  // When at all possible, make a descrete name for your values.
  foreach ($list3 as $volt) {
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE FAN " . $fan . " KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.4.1.2021.13.16.4.1.2.$volt":
         $v=preg_replace('/\ /', '_', $v); // change spaces to _
         $v=preg_replace('/"/','', $v);  // Strip quotes
         $v=preg_replace('/\+/','plus', $v);  // change + to string
         $v=preg_replace('/\-/','minus', $v);  // change - to string
         $v=preg_replace('/\./', '_', $v); // change period to _
         $clean3[$volt]['Name']= "volt.$v" ;
         break;
        case "iso.3.6.1.4.1.2021.13.16.4.1.3.$volt":
         $clean3[$volt]['Value']= "$v" ;
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
  // exit();

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("Name");

  /* Now loop through each interface and create our metric key and value pair */
  foreach ($complete as $mergedList) {
    /* The HARDWARE_NAME should be generic for what metrics we are pushing */
    $graphiteRootKey=$hostname . ".snmp.lmsensors." . $mergedList['Name'];
    foreach ($mergedList as $k => $v) {
      // Match against only values that are numeric and have values to send
      if ($v == 0) { $v = "0";}
      // if ( ! in_array($k, $nonNumericReturns) && !empty($v)) {
      if ( ! in_array($k, $nonNumericReturns) && $v !=='') {
        // echo "KEY " . $k . " VALUE " . $v . "\n";
        $graphiteKey1=$k;
        $graphiteValue=$v;
        /* At this point we have all data needed to send to Graphite */
        // $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
        $graphiteKey=$graphiteRootKey;
        // echo $graphiteKey." ". $graphiteValue. "\n"; // TESTING VALUES
        $returnArrayValues[$graphiteKey]= $graphiteValue;
      } // end if
    }  // end foreach
  }  // end foreach

  // This is called from sendMetricToGraphite.php which has the Graphite class loaded.
  // We must simply give it the parsed and sane data to use at this point
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
