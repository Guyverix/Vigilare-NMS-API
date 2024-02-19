<?php
/* This is going to be called as an include and assuming the template file is found
   will send parsed metric data to RRD.

   Details that must be sent: hostname, metric array for parsing, cycle and checkName
   The templates are standalone, as we need to make readable and sane RRD values keys
   OID: 1.3.6.1.2.1.2.2.1 is for ethernet 32bit counters
*/


  // Convert JSON input back into an array
  $dataToBeInserted=json_decode($dataToBeInserted, true);
  $window = ($cycle * 2);
  // print_r($dataToBeInserted); // DEBUG

  /* create an empty array of our index values */
  $list=array();

  /* Retrieve the ethernet index number for every interface */
  foreach ($dataToBeInserted as $k => $v) {
    // echo "KEY: " . $k . " VALUE " . $v . "\n"; // DEBUG
    if ( strpos($k, '.3.6.1.2.1.2.2.1.1.') !==false) {
      $interfaceIndexNumber=preg_replace('/.*.3.6.1.2.1.2.2.1.1./','', $k);
      $list[]=$interfaceIndexNumber;
    }
  }
  /* test our array is correct, key => interfaceNumber */
  // echo "INTERFACE INDEXES " . print_r($list) . "\n"; // DEBUG
  // exit();  // DEBUG

  /* Create empty array to fill with mapped values */

  $mapped=array();

  foreach ($list as $interface) {
    // echo "INTERFACE = ". $interface . "\n"; // DEBUG
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE " . $interface . " KEY " . $k . " VALUE " . $v . "\n";  // DEBUG
      // echo "KEY FOR SWITCH " . $k . "\n\n";  // DEBUG
      // This will make the array for each rrd DS that we merge back together when we are done.
      // THE NAME must be 19 chars or less!!
      switch ($k) {
        case "iso.3.6.1.2.1.2.2.1.2." .$interface:
         $v=trim($v, " ");
         $v=preg_replace('/[ .,\/]/', '_', $v);
         $v=preg_replace('/"/', '', $v);
         $v=preg_replace('/:/', '', $v);
         $v=preg_replace('/[()]/','', $v);  // Strip out () chars  WTF WinBlows?!?
         $clean[$interface]['ifName']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.3.$interface":
         $v=convertIfType($v);
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifType', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.4.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifMtu', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.5.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifSpeed', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.7.$interface":
         $v = ltrim(rtrim($v));
         if (strpos($v, 'up') !== false) { $v=1; } else { $v=2;}
         // if ($v === "up") { $v=1; } else { $v=2;}  // 1 up, 2 down
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifAdminStatus', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.8.$interface":
         $v = ltrim(rtrim($v));
         if ($v === "up") { $v=1; } else { $v=2;} // 1 up, 2 down, should support 3 since that is an option (rare)
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOperStatus', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.10.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInOctets', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.11.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInUcastPkts', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.12.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInNUcastPkts', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.13.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInDiscards', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.14.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInErrors', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.15.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifInUnknownProtos', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.16.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutOctets', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.17.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutUcastPkts', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.18.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutNUcastPkts', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.19.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutDiscards', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.20.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutErrors', 'value' => "$v", 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.2.2.1.21.$interface":
         $clean[$interface]['dataToBeInserted'][] = array('name' => 'ifOutLen', 'value' => "$v", 'type' => 'GAUGE') ;
         break;
      }
    }
    $mapped[]= $clean[$interface];
  }

  /* Returns mapped[0-2][keys] => values for example above */
  // echo "MAPPED ARRAY " . print_r($mapped) . "\n";   // DEBUG

  $rrdReturnData=array();
  /* Now loop through each interface and define create and update values */
  foreach ($mapped as $interfaces) {
    // filename per interface. Metrics are INSIDE the file for the interface
    $rrdRootFile = $hostname . "/snmp/interfaces/" . $interfaces['ifName']. "_32.rrd";
    // echo "FILENAME :" . $rrdRootFile . "\n";  // DEBUG

    $rrdReturnData[$interfaces['ifName']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';
    // go through each name and add it into create and update array for each interface
    foreach ($interfaces['dataToBeInserted'] as $dataToAdd) {
      $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
      $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
    } // end foreach

    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);

    $rrdReturnData[$interfaces['ifName']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$interfaces['ifName']]['update']=$rrdReturnDataUpdate;
  } // end foreach
  $this->returnArrayValues=$rrdReturnData;
  //   print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

?>
