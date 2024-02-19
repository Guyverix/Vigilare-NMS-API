<?php


// Enable Eventing support for daemon
require __DIR__ . '/../../app/Curl.php';


require __DIR__ . '/../../src/Infrastructure/Shared/Functions/daemonFunctions.php';
$apiHost="http://localhost";
$apiHostname="http://localhost";
$apiPort=8002;
$cycle=300;

$monitorType="snmp";
$iterationCycle="300";
$monitorCycle=300;

//  $pullMonitors2 = pullMonitors($monitorType, $iterationCycle);
//  $pullMonitorsCount=count($pullMonitors);
//  $serviceChecks=json_decode(json_encode($pullMonitors2),true);

//echo  print_r($serviceChecks);
//$pullMonitors2=json_decode($pullMonitors2, true);
//echo print_r($pullMonitors2['data']);
//exit();

//  $monitorList = getMonitor($apiHostname, $apiPort, $cycle);
//  print_r($monitorList);
/*
echo   print_r($pullMonitors2['data']);
exit();

  // Our list of monitors and all host details that we have in hostProperties
  $monitorListHostDetails = getMonitorHostDetails($pullMonitors2['data']);

  print_r($monitorListHostDetails);
*/

/*
echo "PULL " . $pullMonitors2 . "\n\n";


  $pullMonitors = new Curl();
  $pullMonitors->url = $apiHost . ":" . $apiPort . "/monitoringPoller/" . $monitorType . "?cycle=" . $monitorCycle;
  $data = $pullMonitors->send();
  $pullMonitors->close();
  $data=$pullMonitors->content();
//echo $data;
echo print_r($data);
*/

$nrpeResults="OK - 53.5% (17552100 kB) free.|TOTAL=32780696KB;;;; USED=15228596KB;29502626;31141661;; FREE=17552100KB;;;; CACHES=15847980KB;;;;";
//$nrpeResults="OK - 53.5% (17552100 kB) free.| this is invalud";
$test = cleanNrpeMetrics($nrpeResults);

echo "RESULTS\n";
print_r($test);
//echo "TEST " . $test . "\n";

echo "TEST ". $test['perf'] . "\n";


//$oidMetric1='{"iso.3.6.1.4.1.2021.4.1.0":"0","iso.3.6.1.4.1.2021.4.2.0":"swap","iso.3.6.1.4.1.2021.4.3.0":"2097148","iso.3.6.1.4.1.2021.4.4.0":"1944828","iso.3.6.1.4.1.2021.4.5.0":"20535340","iso.3.6.1.4.1.2021.4.6.0":"377244","iso.3.6.1.4.1.2021.4.11.0":"2322072","iso.3.6.1.4.1.2021.4.12.0":"16000","iso.3.6.1.4.1.2021.4.13.0":"12036","iso.3.6.1.4.1.2021.4.14.0":"1030816","iso.3.6.1.4.1.2021.4.15.0":"16563196","iso.3.6.1.4.1.2021.4.100.0":"0","iso.3.6.1.4.1.2021.4.101.0":""}';

//echo print_r(json_decode($oidMetric1,true));






?>
