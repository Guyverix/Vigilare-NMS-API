<?php
/*
   This template is to parse SNMP memory statistics

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys

   OID: 1.3.6.1.4.1.2021.4 memory statistics
   https://oidref.com/1.3.6.1.4.1.2021.4
*/

    /* Graphite does not like periods in IP or hostnames */
    $hostname=preg_replace('/\./', '_', $hostname);

    /* Convert our json_encoded string back to array */
    $dataToBeInserted=json_decode($dataToBeInserted);

    /* Create empty array to fill with mapped values */
    $mapped=array();

    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.4.1.2021.4.1.0":
          $v=preg_replace('/\ /', '_', $v);
          $mapped['memIndex']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.2.0":
          $mapped['memErrorName']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.3.0":
          $mapped['memTotalSwap']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.4.0":
          $mapped['memAvailSwap']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.5.0":
          $mapped['memTotalReal']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.6.0":
          $mapped['memAvailReal']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.7.0":
          $mapped['memTotalSwapTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.8.0":
          $mapped['memAvailSwapTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.9.0":
          $mapped['memTotalRealTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.10.0":
          $mapped['memAvailRealTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.11.0":
          $mapped['memTotalFree']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.12.0":
          $mapped['memMinimumSwap']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.13.0":
          $mapped['memShared']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.14.0":
          $mapped['memBuffer']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.15.0":
          $mapped['memCached']= "$v" ;
          break;
        case "iso.1.3.6.1.4.1.2021.4.16.0":
          $mapped['memUsedSwapTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.17.0":
          $mapped['memUsedRealTXT']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.100.0":
          $mapped['memSwapError']= "$v" ;
          break;
        case "iso.3.6.1.4.1.2021.4.101.0":
          $mapped['memSwapErrorMsg']= "$v" ;
          break;
      }
    }

    /* Returns maped[0-2][keys] => values for example above */
    //print_r($mapped);

    /* Returns that are NOT numeric values or we specifically dont care about */
    $nonNumericReturns=array("memSwapErrorMsg", "memIndex", "memErrorName");

    // Now loop through each interface and create our metric key and value pair
    $graphiteRootKey=$hostname . ".snmp.memory";
    $returnArrayValues=array();
    foreach ($mapped as $k => $v) {
      // Match against only values that are numeric
      if ( ! in_array($k, $nonNumericReturns)) {
        $graphiteKey1=$k;
        $graphiteValue=$v;
        // At this point we have all data needed to send to Graphite
        $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
        // echo $graphiteRootKey.".".$graphiteKey." ". $graphiteValue. "\n";
        $returnArrayValues[$graphiteKey]= $graphiteValue;
      } // end if
    } // end foreach
  //print_r($returnArrayValues);
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
