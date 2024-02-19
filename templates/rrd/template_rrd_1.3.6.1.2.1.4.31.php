<?php
/*
   Only these three values are critical
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

   The file name MUST match $oidCheck value so the main script can find this file.

   OID 1.3.6.1.2.1.4.31
   this contains the 32 bit counters ethernet statistics
*/

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  // print_r($dataToBeInserted);

  /* create an empty array to use */
  $list=array();
  $mapped=array();
  $clean=array();
  $rrdReturnData=array();

  foreach ($dataToBeInserted as $k => $v) {
    // echo "KEY " . $k . " VALUE " . $v . "\n";  // DEBUG
    if ( strpos($k, '.3.6.1.4.1.2021.13.15.1.1.1.') !==false) {
      $tempIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.13.15.1.1.1./','', $k);
      $list[]=$tempIndexNumber;
      // Results will simply be integers that we use later
    } // end if
  }  // end foreach

  /* test our array is correct, key => index number */
  // print_r($list); // DEBUG
  // exit();  // DEBUG

  foreach ($list as $searchValue) {
    foreach ($dataToBeInserted as $k => $v) {
      $v=trim($v);
      // echo "KEY " . $k . " VALUE " . $v . "\n";
      switch ($k) {
        case "iso.3.6.1.4.1.2021.13.15.1.1.2.$searchValue":
          $v=trim($v, " ");
          $v=preg_replace('/[ .,\/]/', '_', $v);
          $v=preg_replace('/"/', '', $v);
          $clean[$searchValue]['diskIODevice']= "$v" ;  // string
          // echo "DEVICE : " . $v . "\n";
          break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.3.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIONRead', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.4.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIONWritten', 'value' => "$v", 'type' => 'COUNTER') ; // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.5.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIOReads', 'value' => "$v", 'type' => 'COUNTER') ;  // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.6.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIOWrites', 'value' => "$v", 'type' => 'COUNTER') ;  // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.9.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIOLA1', 'value' => "$v", 'type' => 'GAUGE') ;  // int
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.12.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIONReadX', 'value' => "$v", 'type' => 'COUNTER') ; // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.13.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIONWrittenX', 'value' => "$v", 'type' => 'COUNTER') ; // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.14.$searchValue":
         $clean[$searchValue]['dataToBeInserted'][] = array('name' => 'diskIOBusyTime', 'value' => "$v", 'type' => 'COUNTER') ; // couter32
         break;
      }
    }
    $mapped[]= $clean[$searchValue];
  }

  /* Returns maped[0-2][keys] => values for example above */
  // print_r($mapped);

  /* Returns that are NOT numeric values or we specifically dont care about */
  // $nonNumericReturns=array("diskIODevice");  // Unused for RRD
  $window = ($cycle * 3);
  // $hostname=preg_replace('/\./', '_', $hostname); // Unused for RRD


  /* Now loop through each interface and define create and update values */
  foreach ($mapped as $searchArray) {
    // print_r($searchArray);
    // filename per interface. Metrics are INSIDE the file for the interface
    $rrdRootFile = $hostname . "/snmp/drive/statistics/" . $searchArray['diskIODevice'] . "_32.rrd";
    // echo "FILENAME :" . $rrdRootFile . "\n";  // DEBUG

    $rrdReturnData[$searchArray['diskIODevice']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';
    // go through each name and add it into create and update array for each interface
    foreach ($searchArray['dataToBeInserted'] as $dataToAdd) {
      $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
      $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
    } // end foreach
    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);

    $rrdReturnData[$searchArray['diskIODevice']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$searchArray['diskIODevice']]['update']=$rrdReturnDataUpdate;
  } // end foreach
  //exit();

  $this->returnArrayValues=$rrdReturnData;
  //print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

?>
