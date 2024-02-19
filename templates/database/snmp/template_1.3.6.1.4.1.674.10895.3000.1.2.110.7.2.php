<?php
/*
   Dell 6248 Chassis best effort for health
   It appears Dell SUCKS at giving metrics for free.
   Many parts are not reporting even with software updates.

   oid ending in 7.1 is FAN
   oid ending in 7.2 is chassis power

  chassis: SOURCE unknown(1), ac(2), dc(3), externalPowerSupply(4), internalRedundant(5)
  Chassis: STATE Valid values are:
            normal(1): the environment is good, such as low temperature.
            warning(2): the environment is bad, such as temperature above normal operation range but not too high.
            critical(3): the environment is very bad, such as temperature much higher than normal operation limit.
            shutdown(4): the environment is the worst, the system should be shutdown immediately.
            notPresent(5): the environmental monitor is not present, such as temperature sensors do not exist.
            notFunctioning(6)

   Only these three values are critical
     $this->returnArrayValues // our array return for the object
     $hostname // fqdn or IP of host
     $dataToBeInserted // The array of data to parse in json_encoded format

   OID: 1.3.6.1.4.1.674.10895.3000.1.2.110.7.1
   https://oidref.com/1.3.6.1.4.1.674.10895.3000.1.2.110.7.1
   http://www.circitor.fr/Mibs/Html/D/Dell-Vendor-MIB.php#envMonFanState

   The file name MUST match $oidCheck value so the main script can find this file.
   template_1.3.6.1.4.1.30911.php (example) if we pulled metrics from that specific oid.

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);



  /* create an empty array of our index values */
  $list=array();

  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.1.') !==false) {
      $interfaceIndexNumber=preg_replace('/.*.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.1./','', $k);
      $list[]=$interfaceIndexNumber;
    } // end if
  }  // end foreach


  /* Create empty array to fill with mapped values */
  $mapped=array();
  $clean=array();
  foreach ($list as $interface) {
    // echo "INTERFACE = ". $interface . "\n";
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE KEY " . $k . " VALUE " . $v . "\n";
      if (empty($v)) { $v='';}
      switch ($k) {
        case "iso.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.2.$interface":
          $v=preg_replace('/\ /', '_', $v);
          $v=preg_replace('/"/', '', $v);
          $clean[$interface]['chassisNames'] = $v ;
          break;
        case "iso.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.3.$interface":
          $clean[$interface]['powerState'] = "$v" ;
          break;
        case "iso.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.1.$interface":
          $clean[$interface]['chassisIndex'] = "$v" ;
          break;
        case "iso.3.6.1.4.1.674.10895.3000.1.2.110.7.2.1.4.$interface":
          $clean[$interface]['powerSource'] = "$v" ;
          break;
        }
      }
      $mapped[] = $clean[$interface];
    }

  /* Returns mapped[keys] => values for example above */
  //print_r($mapped);
  // exit();

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("chassisNames", "chassisIndex");

  /* The HARDWARE_NAME should be generic for what metrics we are pushing */
  $graphiteRootKey=$hostname . ".chassis.";
  $returnArrayValues=array();
  foreach ($mapped as $interfaces) {
    // print_r($interfaces);
    // Now loop through each interface and create our metric key and value pair
    $graphiteRootKey=$hostname . ".snmp.chassis." . $interfaces['chassisNames'];
    foreach ($interfaces as $k => $v) {
      // Match against only values that are numeric AND are datafilled
      if ( ! in_array($k, $nonNumericReturns) && !empty($v)) {
        $graphiteKey1=$k;
        $graphiteValue=$v;
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
