<?php
/* This is going to be called as an include and assuming the template file is found
   will send parsed metric data to Graphite.

   Details that must be sent: hostname, metric array for parsing
   The templates are standalone, as we need to make readable and sane Graphite keys
   OID: 1.3.6.1.2.1.2.2.1 is for ethernet 64bit counters

  SNMP V1 or V2 32 bit counters
*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

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
        case "iso.3.6.1.2.1.2.2.1.2." .$interface:
         $v=trim($v, " ");
         $v=preg_replace('/[ .,\/]/', '_', $v);
         $v=preg_replace('/"/', '', $v);
         $clean[$interface]['ifName']= "$v" ;
         break;
        //case "iso.3.6.1.2.1.2.2.1.2.$interface":
        // $clean[$interface]['ifDescr']= "$v" ;
        // break;
        case "iso.3.6.1.2.1.2.2.1.3.$interface":
         $clean[$interface]['ifType']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.4.$interface":
         $clean[$interface]['ifMtu']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.5.$interface":
         $clean[$interface]['ifSpeed']= "$v" ;
         break;
        //case "iso.3.6.1.2.1.2.2.1.6.$interface":
        // $clean[$interface]['ifPhyAddress']= "$v" ;
        // break;
        case "iso.3.6.1.2.1.2.2.1.7.$interface":
         $clean[$interface]['ifAdminStatus']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.8.$interface":
         $clean[$interface]['ifOperStatus']= "$v" ;
         break;
        //case "iso.3.6.1.2.1.2.2.1.9.$interface":
        // $clean[$interface]['ifLastChange']= "$v" ;
        // break;
        case "iso.3.6.1.2.1.2.2.1.10.$interface":
         $clean[$interface]['ifInOctets']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.11.$interface":
         $clean[$interface]['ifInUcastPkts']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.12.$interface":
         $clean[$interface]['ifInNUcastPkts']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.13.$interface":
         $clean[$interface]['ifInDiscards']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.14.$interface":
         $clean[$interface]['ifInErrors']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.15.$interface":
         $clean[$interface]['ifInUnknownProtos']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.16.$interface":
         $clean[$interface]['ifOutOctets']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.17.$interface":
         $clean[$interface]['ifOutUcastPkts']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.18.$interface":
         $clean[$interface]['ifOutNUcastPkts']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.19.$interface":
         $clean[$interface]['ifOutDiscards']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.20.$interface":
         $clean[$interface]['ifOutErrors']= "$v" ;
         break;
        case "iso.3.6.1.2.1.2.2.1.21.$interface":
         $clean[$interface]['ifOutLen']= "$v" ;
         break;
      }
    }
    $mapped[]= $clean[$interface];
  }

  /* Returns maped[0-2][keys] => values for example above */
  //print_r($mapped);

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("ifName", "ifAlias", "ifCounterDiscontinuityTime");

  /* Now loop through each interface and create our metric key and value pair */
  foreach ($mapped as $interfaces) {
    $graphiteRootKey=$hostname . ".snmp.interfaces." . $interfaces['ifName']."_32";
    foreach ($interfaces as $k => $v) { 
      /* Match against only values that are numeric */
      if ( ! in_array($k, $nonNumericReturns)) {
        $graphiteKey1=$k;
        $graphiteValue=$v;
        /* At this point we have all data needed to send to Graphite */
        $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
        // echo $graphiteRootKey.".".$graphiteKey." ". $graphiteValue. "\n";
        $returnArrayValues[$graphiteKey]= $graphiteValue;
      }
    }
  }
  $this->returnArrayValues=$returnArrayValues;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

/*
$rawOidMetric1='{"iso.3.6.1.2.1.2.2.1.1.1":"lo","iso.3.6.1.2.1.2.2.1.1.2":"enp2s0f0","iso.3.6.1.2.1.2.2.1.1.3":"enp2s0f1","iso.3.6.1.2.1.2.2.1.2.1":"0","iso.3.6.1.2.1.2.2.1.2.2":"2702651","iso.3.6.1.2.1.2.2.1.2.3":"2702651","iso.3.6.1.2.1.2.2.1.3.1":"0","iso.3.6.1.2.1.2.2.1.3.2":"0","iso.3.6.1.2.1.2.2.1.3.3":"0","iso.3.6.1.2.1.2.2.1.4.1":"0","iso.3.6.1.2.1.2.2.1.4.2":"0","iso.3.6.1.2.1.2.2.1.4.3":"0","iso.3.6.1.2.1.2.2.1.5.1":"0","iso.3.6.1.2.1.2.2.1.5.2":"0","iso.3.6.1.2.1.2.2.1.5.3":"0","iso.3.6.1.2.1.2.2.1.6.1":"1384189","iso.3.6.1.2.1.2.2.1.6.2":"508693214562","iso.3.6.1.2.1.2.2.1.6.3":"6430735905949","iso.3.6.1.2.1.2.2.1.7.1":"15445","iso.3.6.1.2.1.2.2.1.7.2":"380927641","iso.3.6.1.2.1.2.2.1.7.3":"4606284698","iso.3.6.1.2.1.2.2.1.8.1":"0","iso.3.6.1.2.1.2.2.1.8.2":"2702651","iso.3.6.1.2.1.2.2.1.8.3":"2702651","iso.3.6.1.2.1.2.2.1.9.1":"0","iso.3.6.1.2.1.2.2.1.9.2":"0","iso.3.6.1.2.1.2.2.1.9.3":"0","iso.3.6.1.2.1.2.2.1.10.1":"1384189","iso.3.6.1.2.1.2.2.1.10.2":"41832352","iso.3.6.1.2.1.2.2.1.10.3":"675648640949","iso.3.6.1.2.1.2.2.1.11.1":"15445","iso.3.6.1.2.1.2.2.1.11.2":"243102","iso.3.6.1.2.1.2.2.1.11.3":"1288665042","iso.3.6.1.2.1.2.2.1.12.1":"0","iso.3.6.1.2.1.2.2.1.12.2":"0","iso.3.6.1.2.1.2.2.1.12.3":"0","iso.3.6.1.2.1.2.2.1.13.1":"0","iso.3.6.1.2.1.2.2.1.13.2":"0","iso.3.6.1.2.1.2.2.1.13.3":"0","iso.3.6.1.2.1.2.2.1.15.1":"10","iso.3.6.1.2.1.2.2.1.15.2":"1000","iso.3.6.1.2.1.2.2.1.15.3":"1000","iso.3.6.1.2.1.2.2.1.16.1":"2","iso.3.6.1.2.1.2.2.1.16.2":"2","iso.3.6.1.2.1.2.2.1.16.3":"2","iso.3.6.1.2.1.2.2.1.17.1":"2","iso.3.6.1.2.1.2.2.1.17.2":"1","iso.3.6.1.2.1.2.2.1.17.3":"1","iso.3.6.1.2.1.2.2.1.18.1":"","iso.3.6.1.2.1.2.2.1.18.2":"","iso.3.6.1.2.1.2.2.1.18.3":"","iso.3.6.1.2.1.2.2.1.19.1":"0","iso.3.6.1.2.1.2.2.1.19.2":"0","iso.3.6.1.2.1.2.2.1.19.3":"0"}';
$hostname1="larvel01.iwillfearnoevil.com";
sendMetricToGraphite( $hostname1, $rawOidMetric1);
*/
?>
