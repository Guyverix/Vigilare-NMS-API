<?php
/* This is going to be called as an include and assuming the template file is found
   will send parsed metric data to Graphite.

   Details that must be sent: hostname, metric array for parsing
   The templates are standalone, as we need to make readable and sane Graphite keys
   OID: 1.3.6.1.2.1.31.1.1.1 is for ethernet 64bit counters

  SNMP V2 64 bit counters ifName_64
*/

  $dataToBeInserted=json_decode($dataToBeInserted, true);
  // print_r($dataToBeInserted); // DEBUG

  /* create an empty array of our index values */
  $list=array();
  $window = ($cycle * 3);

  /* Retrieve the ethernet index number for every interface */
  foreach ($dataToBeInserted as $k => $v) {
    // echo "KEY: " . $k . " VALUE " . $v . "\n"; // DEBUG
    if ( strpos($k, '.3.6.1.2.1.31.1.1.1.1.') !==false) {
      $interfaceIndexNumber=preg_replace('/.*.3.6.1.2.1.31.1.1.1.1./','', $k);
      $list[]=$interfaceIndexNumber;
    }
  }
  /* test our array is correct, key => interfaceNumber */
   //print_r($list); // DEBUG
   //exit();  // DEBUG
  /* Create empty array to fill with mapped values */
  $mapped=array();

  foreach ($list as $interface) {
    // echo "INTERFACE = ". $interface . "\n"; // DEBUG
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE " . $interface . " KEY " . $k . " VALUE " . $v . "\n";  // DEBUG
      // echo "KEY FOR SWITCH " . $k . "\n\n";
      switch ($k) {
        case "iso.3.6.1.2.1.31.1.1.1.1." .$interface:
         $v=ltrim(rtrim($v));
         $v=preg_replace('/\ /', '_', $v);
         $v=preg_replace('/"/', '', $v);
         $v=preg_replace('/:/', '', $v);
         $clean[$interface]['ifName']  = "$v" ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.2.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'InMulticastPkts', 'value' => $v, 'type' => 'COUNTER');
         break;
        case "iso.3.6.1.2.1.31.1.1.1.3.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'InBroadcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.4.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'OutMulticastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.5.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'OutBroadcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.6.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCInOctets', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.7.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCInUcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.8.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCInMulticastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.9.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCInBroadcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.10.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCOutOctets', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.11.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCOutUcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.12.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCOutMulticastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.13.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HCOutBroadcastPkts', 'value' => $v, 'type' => 'COUNTER') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.14.$interface":
         if ( $v == "enabled" ) { $v = 0; } else { $v = 1; }
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'LinkUpDownTrapEnable', 'value' => $v, 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.15.$interface":
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'HighSpeed', 'value' => $v, 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.16.$interface":
         if ( $v === "false" ) { $ins = 1; } else { $ins = 0; }
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'PromiscuousMode', 'value' => $ins, 'type' => 'GAUGE') ;
         break;
        case "iso.3.6.1.2.1.31.1.1.1.17.$interface":
         if ( $v == "false" ) { $ins = 1; } else { $ins = 0; }
         $clean[$interface]['dataToBeInserted'][] = array( 'name' => 'ConnectorPresent', 'value' => $ins, 'type' => 'GAUGE') ;
         break;
      }
    }
    $mapped[]= $clean[$interface];
  }

  /* Returns maped[0-2][keys] => values for example above */
  //print_r($mapped);

  $rrdReturnData=array();
  /* Now loop through each interface and create our metric key and value pair */
  foreach ($mapped as $interfaces) {
    $rrdRootFile = $hostname . "/snmp/interfaces/" . $interfaces['ifName'] . "_64.rrd";

    $rrdReturnData[$interfaces['ifName']]['fileName'] = $rrdRootFile;
    $rrdReturnDataUpdate="N";  // Default for update to set "NOW" in rrd style
    $rrdReturnDataCreate='';

    foreach ($interfaces['dataToBeInserted'] as $dataToAdd) {
      $rrdReturnDataCreate .= "DS:" . $dataToAdd['name'] . ":" . $dataToAdd['type'] . ":" . $window . ":0:U ";
      $rrdReturnDataUpdate .= ":" . $dataToAdd['value'];
    } // end foreach
    $rrdReturnDataUpdate = preg_replace('/[ ]/', '', $rrdReturnDataUpdate);

    $rrdReturnData[$interfaces['ifName']]['create']=$rrdReturnDataCreate;
    $rrdReturnData[$interfaces['ifName']]['update']=$rrdReturnDataUpdate;
  } // end foreach

  $this->returnArrayValues=$rrdReturnData;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

/*
$rawOidMetric1='{"iso.3.6.1.2.1.31.1.1.1.1.1":"lo","iso.3.6.1.2.1.31.1.1.1.1.2":"enp2s0f0","iso.3.6.1.2.1.31.1.1.1.1.3":"enp2s0f1","iso.3.6.1.2.1.31.1.1.1.2.1":"0","iso.3.6.1.2.1.31.1.1.1.2.2":"2702651","iso.3.6.1.2.1.31.1.1.1.2.3":"2702651","iso.3.6.1.2.1.31.1.1.1.3.1":"0","iso.3.6.1.2.1.31.1.1.1.3.2":"0","iso.3.6.1.2.1.31.1.1.1.3.3":"0","iso.3.6.1.2.1.31.1.1.1.4.1":"0","iso.3.6.1.2.1.31.1.1.1.4.2":"0","iso.3.6.1.2.1.31.1.1.1.4.3":"0","iso.3.6.1.2.1.31.1.1.1.5.1":"0","iso.3.6.1.2.1.31.1.1.1.5.2":"0","iso.3.6.1.2.1.31.1.1.1.5.3":"0","iso.3.6.1.2.1.31.1.1.1.6.1":"1384189","iso.3.6.1.2.1.31.1.1.1.6.2":"508693214562","iso.3.6.1.2.1.31.1.1.1.6.3":"6430735905949","iso.3.6.1.2.1.31.1.1.1.7.1":"15445","iso.3.6.1.2.1.31.1.1.1.7.2":"380927641","iso.3.6.1.2.1.31.1.1.1.7.3":"4606284698","iso.3.6.1.2.1.31.1.1.1.8.1":"0","iso.3.6.1.2.1.31.1.1.1.8.2":"2702651","iso.3.6.1.2.1.31.1.1.1.8.3":"2702651","iso.3.6.1.2.1.31.1.1.1.9.1":"0","iso.3.6.1.2.1.31.1.1.1.9.2":"0","iso.3.6.1.2.1.31.1.1.1.9.3":"0","iso.3.6.1.2.1.31.1.1.1.10.1":"1384189","iso.3.6.1.2.1.31.1.1.1.10.2":"41832352","iso.3.6.1.2.1.31.1.1.1.10.3":"675648640949","iso.3.6.1.2.1.31.1.1.1.11.1":"15445","iso.3.6.1.2.1.31.1.1.1.11.2":"243102","iso.3.6.1.2.1.31.1.1.1.11.3":"1288665042","iso.3.6.1.2.1.31.1.1.1.12.1":"0","iso.3.6.1.2.1.31.1.1.1.12.2":"0","iso.3.6.1.2.1.31.1.1.1.12.3":"0","iso.3.6.1.2.1.31.1.1.1.13.1":"0","iso.3.6.1.2.1.31.1.1.1.13.2":"0","iso.3.6.1.2.1.31.1.1.1.13.3":"0","iso.3.6.1.2.1.31.1.1.1.15.1":"10","iso.3.6.1.2.1.31.1.1.1.15.2":"1000","iso.3.6.1.2.1.31.1.1.1.15.3":"1000","iso.3.6.1.2.1.31.1.1.1.16.1":"2","iso.3.6.1.2.1.31.1.1.1.16.2":"2","iso.3.6.1.2.1.31.1.1.1.16.3":"2","iso.3.6.1.2.1.31.1.1.1.17.1":"2","iso.3.6.1.2.1.31.1.1.1.17.2":"1","iso.3.6.1.2.1.31.1.1.1.17.3":"1","iso.3.6.1.2.1.31.1.1.1.18.1":"","iso.3.6.1.2.1.31.1.1.1.18.2":"","iso.3.6.1.2.1.31.1.1.1.18.3":"","iso.3.6.1.2.1.31.1.1.1.19.1":"0","iso.3.6.1.2.1.31.1.1.1.19.2":"0","iso.3.6.1.2.1.31.1.1.1.19.3":"0"}';
$hostname1="larvel01.iwillfearnoevil.com";
sendMetricToGraphite( $hostname1, $rawOidMetric1);
*/
?>
