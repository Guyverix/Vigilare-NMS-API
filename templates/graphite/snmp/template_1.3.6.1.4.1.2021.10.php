<?php
/*
   This template is to parse SNMP Load averages (think top values)

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys

   OID: 1.3.6.1.4.1.2021.10 load statistics
   https://oidref.com/1.3.6.1.4.1.2021.10
*/

    $hostname=preg_replace('/\./', '_', $hostname);
    $dataToBeInserted=json_decode($dataToBeInserted, true);

    /* create an empty array of our index values */
    $list=array();

    // Get our indexes for 1,5,15 load averages
    // dont forget the last period in the string match values silly!
    foreach ($dataToBeInserted as $k => $v) {
      if ( strpos($k, '.3.6.1.4.1.2021.10.1.1.') !==false) {
        $interfaceIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.10.1.1./','', $k);
        $list[]=$interfaceIndexNumber;
      }
    }
    /* test our array is correct */
    // print_r($list);

    /* Create empty array to fill with mapped values */
    $mapped=array();
    $clean=array();
    foreach ($list as $interface) {
      // echo "INTERFACE = ". $interface . "\n";
      foreach ($dataToBeInserted as $k => $v) {
        // echo "VALUE KEY " . $k . " VALUE " . $v . "\n";
        if (empty($v)) { $v='';}
        switch ($k) {
          case "iso.3.6.1.4.1.2021.10.1.2.$interface":
            $v=preg_replace('/\ /', '_', $v);
            $clean[$interface]['laNames'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.3.$interface":
            $clean[$interface]['laLoad'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.4.$interface":
            $clean[$interface]['laConfig'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.5.$interface":
            $clean[$interface]['laLoadInt'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.6.$interface":
            $clean[$interface]['laLoadFloat'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.100.$interface":
            $clean[$interface]['laErrorFlag'] = "$v" ;
            break;
          case "iso.3.6.1.4.1.2021.10.1.101.$interface":
            $clean[$interface]['laErrorMessage'] = "$v" ;
            break;
        }
      }
      $mapped[] = $clean[$interface];
    }
    /* Returns maped[0-3][keys] => values for example above */
    // print_r($mapped);

    /* Returns that are NOT numeric values or we specifically dont care about */
    $nonNumericReturns=array("laNames","laErrorMessage");

    $returnArrayValues=array();
    foreach ($mapped as $interfaces) {
      // print_r($interfaces);
      // Now loop through each interface and create our metric key and value pair
      $interfaces['laNames']=preg_replace('/"/','', $interfaces['laNames']);
      $graphiteRootKey=$hostname . ".snmp.load." . $interfaces['laNames'];
      foreach ($interfaces as $k => $v) {
        // Match against only values that are numeric AND are datafilled
        if ( ! in_array($k, $nonNumericReturns) && !empty($v)) {
          $graphiteKey1=$k;
          $graphiteValue=$v;
          $graphiteKey1=preg_replace( '/"/' , '', $graphiteKey1);
          // At this point we have all data needed to send to Graphite
          $graphiteKey=$graphiteRootKey . "." . $graphiteKey1;
          // echo $graphiteRootKey.".".$graphiteKey." ". $graphiteValue. "\n";
          $returnArrayValues[$graphiteKey]= $graphiteValue;
        } // end if
      }  // end foreach
    } // end foreach
   $this->returnArrayValues=$returnArrayValues;
   return $this->returnArrayValues;
  ?>
