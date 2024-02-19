<?php
/*
   This skeleton is going to be used to MAKE an include for pollers to consume
   and will send parsed metric data to Graphite.

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys
   Example:
   OID: 1.3.6.1.2.1.31.1.1.1 ethernet 64bit counters
   OID: 1.3.6.1.4.1.2021.4   memory statistics
   OID: 1.3.6.1.4.1.2021.11  CPU
   OID: 1.3.6.1.4.1.2021.10  Load

   Only these three values are critical
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

   OID: OID we walked
   https://oidref.com/OID we walked

   The file name MUST match $oidCheck value so the main script can find this file.
   template_1.3.6.1.4.1.30911.php (example) if we pulled metrics from that specific oid.


snmpwalk -v2c -c public 192.168.15.58 1.3.6.1.2.1.6.13.1.5
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.22.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.111.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.2049.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.3333.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.8080.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.9090.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.37429.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.38347.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.41413.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.0.0.0.0.58993.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.127.0.0.1.25.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.127.0.0.1.631.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.127.0.0.1.33749.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.127.0.0.1.61209.0.0.0.0.0 = INTEGER: 0
iso.3.6.1.2.1.6.13.1.5.127.0.0.53.53.0.0.0.0.0 = INTEGER: 0



*/
  if ( empty($hostname)) { $hostname='undefinedInTemplate'; }

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

  /* create an empty array of our index values */
  $clean=array();

  // When at all possible, make a descrete name for your values.
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE INTERFACE KEY " . $k . " VALUE " . $v . getType($v) . "\n";
      if ( $v == "0") {
        // These are local listening ports
        $rawIpAddress=explode('.', $k);
        $ipAddress="".$rawIpAddress[10] .'.'. $rawIpAddress[11] .'.'. $rawIpAddress[12] .'.'. $rawIpAddress[13] ."";
        $listeningPort=$rawIpAddress[14];
        // echo "VALUE KEY " . $k . " VALUE " . $v . " ADDRESS " . $ipAddress . " PORT " . $listeningPort . "\n"; // DEBUG
        $clean['address'] = $ipAddress;
        $clean['port'] = $listeningPort;
        $mapped[]=$clean;
      }
    }

  /* Returns mapped[#][keys] => values for example above */
  //  print_r($mapped); // DEBUG
  //   exit(); // DEBUG

  // This is going to be stored in the database, we will convert in the main sendTo script
  $this->returnArrayValues=$mapped;
  return $this->returnArrayValues;
?>
