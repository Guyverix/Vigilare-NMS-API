#!/usr/bin/php
<?php

/*
  Method (function) definitions for all currently supported types of Metric Storage available
  This will be the start of calling the classes, and leveraging them to get the objects returned.
  Likely a real developer would do this differently, but meh.  It makes sense to me.

  Return codes are based on the step in the process.  This will allow for debugging when something chokes.
  Return 1 main method
  Return 2 class method
  Return 3 template script
  Return 4 main method after template completed
  Return 5 actual update with data
*/

require_once __DIR__ . "/generalMetricClass.php";
require_once __DIR__ . '/../src/Infrastructure/Shared/Functions/daemonFunctions.php';
require __DIR__ . "/../app/Logger.php";
$logger = new ExternalLogger("Manual_testing", 0, 300);

// Afer RRD I suspect this will be the next most common metric storage type
function sendMetricToGraphite2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  echo "hostname and IP adresses must never have periods.  Converting now from: " . $hostname . "\n";
  $hostname=preg_replace('/\./', '_', $hostname);
  echo "Changed hostname or IP address is now: " . $hostname . "\n";

  if (! isset($metricData)) {
    echo "Attempting new object\n";
    $metricData = new MetricParsingGraphite();
  }

  if ( null === $checkAction ) {
    echo "checkAction undefined\n";
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, null, $type);
  }
  else {
    echo "checkAction defined\n";
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction, $type);
  }

  if ( $noise == 1) {
    echo "object or template failed to load properly\n";
    return 2;
  }

  $returnArray = $metricData->returnArrayValues;

  if ( ! is_array($returnArray) && $type !== "nrpe" ) {
    echo "Array was not returned from template file\n";
    return 2;
  }
  elseif ($returnArray == 1) {
    echo "Array was not returned from template file\n";
    return 2;
  }

  if (! isset($graphitePush)) {
    // This is going to have to get cleaned and or removed
    require_once __DIR__ . '/../app/Graphite.php';
    $graphitePush = new Graphite();
  }

  foreach ($returnArray as $k => $v) {
    echo "Would have pushed values to graphite key " . $k . " value " . $v . "\n"; 
     $graphitePushNoise = $graphitePush->testMetric( $k, $v);
  }
  unset ($returnArray);
  unset ($graphitePush);
  unset ($noise);
  unset ($metricData);
  return 0;
}

// Helper function for sendMetricToRrd
function rrdExist2($singleRrdFileName, $cycle, $rrdCreateDs) {
  $fileName = __DIR__ . "/../rrd/" . $singleRrdFileName;
  if ( file_exists($fileName)) {
    echo "RRD database file exists\n";
    return 0;
  }
  else {
    $pathParts = pathinfo($fileName);
    if ( ! file_exists($pathParts['dirname'])) {
      echo "Attempting to create path to where RRD database will live\n";
      mkdir($pathParts['dirname'],0777, true);
    }

    if (is_null($cycle)) {
      echo "Cycle value is not set.  Manually setting now if possible\n";
      $findCycle = explode('-',$checkName);
      $cycle = $findCycle[1];
    }

    if ( $cycle == 60) {
      echo "Testing using same RRA averages for 300 seconds inside the 60 second database\n";
      $retention="RRA:AVERAGE:0.5:1:288 RRA:AVERAGE:0.5:3:672 RRA:AVERAGE:0.5:12:744 RRA:AVERAGE:0.5:72:1460";
    }
    else {
      echo "Retention hard set for data agragation of RRD databases\n";
      $retention="RRA:AVERAGE:0.5:1:288 RRA:AVERAGE:0.5:3:672 RRA:AVERAGE:0.5:12:744 RRA:AVERAGE:0.5:72:1460";
    }

    $cmd="rrdtool create " . $fileName . " --step " . $cycle .  " " . $rrdCreateDs . " " . $retention . " ";
    $result=exec($cmd, $output, $exitCode);
    echo "RRD command " . $cmd . "\n";
    if ( $exitCode == 0 ) {
      echo "RRD command success\n";
      return 0;
    }
    else {
      echo "RRD command failed\n";
      return 4;
    }
  }
}

// Helper function for sendMetricToRrd
function rrdUpdate2($singleRrdMetricFileName, $singleRrdMetricUpdate) {
  $fileName = __DIR__ . "/../rrd/" . $singleRrdMetricFileName;
  $cmd="rrdtool update " . $fileName . " " . $singleRrdMetricUpdate . " ";
  $result = exec($cmd, $output, $exitCode);
  echo "RRD command " . $cmd . "\n";
  if ( $exitCode == 0 ) {
    echo "RRD command success\n";
    return 0;
  }
  else {
    echo "RRD command failed\n";
    return 4;
  }
}

// Likely the most common call for metric storage
// RRD is the one that you should give cycle to almost every time
function sendMetricToRrd2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  $checkName = preg_replace('/[ .,]/','', $checkName);
  echo "Cleanup of checkName variable.  Removed space, period, and comma: " . $checkName . "\n";
  if (! isset($metricData)) {
    echo "New object created\n";
    $metricData=new MetricParsingRrd();
  }

  if ( $type == "poller" || $type == "alive" ) {
    echo "Is type " . $type . "\n";
    if (is_null($cycle)) {
      $findCycle=explode('-',$checkName);
      $cycle=$findCycle[1];
      if ( (int)$cycle) {
        echo "Found cycle since it was undefined and set to " . $cycle . "\n";
      }
      else {
        echo "Failed to find cycle from checkName";
        return 1;
      }
    }
  }

  // If we are still null at this point, error out for cycle
  if (is_null($cycle)) {
    echo "cycle has to be defined somehow before this point\n";
    return 1;
  }

  if (is_null($type)) {
    echo "optional type is not set\n";
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction, null, $cycle );
  }
  else {
    echo "type is set\n";
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
  }

  if ( $noise == 1) {
    echo "Object or template failed to execute correctly\n";
    return 2;
  }

  $returnArray = $metricData->returnArrayValues;

  if ( ! is_array($returnArray)) {
    echo "Failed to return array from template file\n";
    return 3;
  }
  elseif ($returnArray == 1) {
    echo "Failed to return array and noticed by object that it was not created\n";
    return 2;
  }

  foreach ($returnArray as $singleRrd) {
    echo "Calling method to check that an RRD database file exists\n";
    $dbSetup = rrdExist($singleRrd['fileName'],$cycle, $singleRrd['create'] );
    if ($dbSetup !== 0) {
      echo "RRD database file does not exist and cannot be created\n";
      return 4;
    }
  }

  foreach ($returnArray as $singleRrdMetrics) {
    echo "DRY RUN Calling method rrdUpdate to finally insert data into the existing RRD database file\n";
    echo "fileName " . $singleRrdMetrics['fileName'] . " metrics to insert " . $singleRrdMetrics['update'] . "\n";
    // $dbUpdate=rrdUpdate($singleRrdMetrics['fileName'], $singleRrdMetrics['update']);
  }
  unset ($returnArray);
  unset ($metricData);
  return 0;
}

// Likely used for slow iteration cycle things to store that will be pulled commonly but not evented on. IE portsUsed
function sendMetricToDatabase2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  if (! isset($metricData)) {
    echo "Create new object\n";
    $metricData = new MetricParsingDatabase();
  }
  $result=$metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  if ( $result == 1) {
    echo "Failed to parse in the object or template the results we needed\n";
    return 2;
  }

  $returnArray = $metricData->returnArrayValues;
  if (empty($returnArray)) {
    echo "Did not receive an array back from the template or object call\n";
    return 3;
  }

  // If it is already a string, dont double encode it
  if ( is_array($returnArray)) {
    $returnArray=json_encode($returnArray,1);
  }
  echo "DRY RUN Sending data to daemonFuctions.php to send to the database\n";
  echo $returnArray . "\n";
  // $result=sendPerformanceDatabase($hostname, $checkName, $returnArray);
  unset ($returnArray);
  unset ($metricData->returnArrayValues);
  if ( $result == 1) { return 3; }
  return 0;
}

// Likely this will be little used.  Storing metrics into a file is kinda useless with databases available
function sendMetricToFile2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  if (! isset($metricData)) {
    echo "Create new object\n";
    $metricData=new MetricParsingFile();
  }

  $result = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  if ( $result == 1 ) {
    echo "Object failed to create properly\n";
    return 2;
  }

  $fileName = __DIR__ . "/../file/" . $hostname . "/" . $checkName . ".txt";
  if ( ! file_exists($pathParts['dirname'])) {
    echo "Creating path for file to be written to\n";
    mkdir($pathParts['dirname'],0777, true);
  }
  file_put_contents($fileName, $dataToBeInserted);
  echo "Inserted data from dataToBeInserted into the file\n";

  unset ($returnArray);
  unset ($metricData);
  return 0;
}

// Basically a var dump to file specific to metrics attempting to be inserted into a storage type
function sendMetricToDebugger2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  if (! isset($metricData)) {
    $metricData=new MetricParsingFileDebugger();
  }
  $result = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  if ( $result == 1 ) {
    echo "Failed to create object or directory to store data in the filesystem\n";
    return 2;
  }

  $fileName = __DIR__ . "/../file/" . $hostname . "/debugger/" . $checkName . ".txt";
  // Do this here instead of in the class to keep debugging simpler.
  echo "Inserted file data to " . $fileName . "\n";
  $allData=array('hostname' =>$hostname, 'dataToBeInserted' => $dataToBeInserted, 'checkName' => $checkName, 'checkAction' => $checkAction, 'type' => $type, 'cycle' => $cycle);
  file_put_contents($fileName, json_encode($allData,1));

  echo "Pulling from file and converting back from JSON to array output\n";
  $allDataConvert = file_get_contents("$fileName");
  $allDataConvert = json_decode($allDataConvert,1);
  print_r($allDataConvert);
  unset ($metricData);
  return 0;
}

/* TODO if actually needed in the future */
function sendMetricToInflux2($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  echo "Not implemented.\n";
  return 1;
}

/* Any new storage types will have the method calling them defined here */


/* Metrics to be tested manually go below here.  Follow example */

/*
// Test basic string saves
$hostname='test.foo.bar';
$checkName='randomCheckName';
$checkAction='some random action or oid being walked';
$type='testing';
$cycle=9000;
$dataToBeInserted='{ "foo" = "bar" }';
sendMetricToDebugger($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
*/


/*
// Testing snmp/walk/get type
$checkName='dellFan';
$checkAction='1.3.6.1.4.1.674.10895.3000.1.2.110.7.1';
$hostname='test.foo.bar.iwillfearnoevil.com';
$type='walk';
$dataToBeInserted='{"iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.1.11":"11","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.1.12":"12","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.1.13":"13","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.1.14":"14","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.2.11":"\"Fan 1\"","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.2.12":"\"Fan 2\"","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.2.13":"\"Fan 3\"","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.2.14":"\"Fan 4\"","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.3.11":"1","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.3.12":"1","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.3.13":"1","iso.3.6.1.4.1.674.10895.3000.1.2.110.7.1.1.3.14":"1"}';
sendMetricToGraphite($hostname, $dataToBeInserted, $checkName, $checkAction, $type, null);
*/

/*
// Testing NRPE type
$type        = 'nrpe';
$cycle       = "3000";
$checkName   = "randomCheckName";
$checkAction = "random shell command -w 2 -c 3 2>&1";
$hostname    = 'test.foo.bar.iwillfearnoevil.com';
$dataToBeInserted = json_encode("[\"TCP OK - 0.000 second response time on localhost port 22|time=0.000228s;;;0.000000;10.000000\"]",1);
$test=sendMetricToDatabase($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
echo "Test Results: (0 is a success) " . $test . "\n";
*/

/*
// Testing 64 bit counters ethernet interfaces for rrd
$dataToBeInserted='{"iso.3.6.1.2.1.31.1.1.1.1.1":"lo","iso.3.6.1.2.1.31.1.1.1.1.2":"enp2s0f0","iso.3.6.1.2.1.31.1.1.1.1.3":"enp2s0f1","iso.3.6.1.2.1.31.1.1.1.2.1":"0","iso.3.6.1.2.1.31.1.1.1.2.2":"2702651","iso.3.6.1.2.1.31.1.1.1.2.3":"2702651","iso.3.6.1.2.1.31.1.1.1.3.1":"0","iso.3.6.1.2.1.31.1.1.1.3.2":"0","iso.3.6.1.2.1.31.1.1.1.3.3":"0","iso.3.6.1.2.1.31.1.1.1.4.1":"0","iso.3.6.1.2.1.31.1.1.1.4.2":"0","iso.3.6.1.2.1.31.1.1.1.4.3":"0","iso.3.6.1.2.1.31.1.1.1.5.1":"0","iso.3.6.1.2.1.31.1.1.1.5.2":"0","iso.3.6.1.2.1.31.1.1.1.5.3":"0","iso.3.6.1.2.1.31.1.1.1.6.1":"1384189","iso.3.6.1.2.1.31.1.1.1.6.2":"508693214562","iso.3.6.1.2.1.31.1.1.1.6.3":"6430735905949","iso.3.6.1.2.1.31.1.1.1.7.1":"15445","iso.3.6.1.2.1.31.1.1.1.7.2":"380927641","iso.3.6.1.2.1.31.1.1.1.7.3":"4606284698","iso.3.6.1.2.1.31.1.1.1.8.1":"0","iso.3.6.1.2.1.31.1.1.1.8.2":"2702651","iso.3.6.1.2.1.31.1.1.1.8.3":"2702651","iso.3.6.1.2.1.31.1.1.1.9.1":"0","iso.3.6.1.2.1.31.1.1.1.9.2":"0","iso.3.6.1.2.1.31.1.1.1.9.3":"0","iso.3.6.1.2.1.31.1.1.1.10.1":"1384189","iso.3.6.1.2.1.31.1.1.1.10.2":"41832352","iso.3.6.1.2.1.31.1.1.1.10.3":"675648640949","iso.3.6.1.2.1.31.1.1.1.11.1":"15445","iso.3.6.1.2.1.31.1.1.1.11.2":"243102","iso.3.6.1.2.1.31.1.1.1.11.3":"1288665042","iso.3.6.1.2.1.31.1.1.1.12.1":"0","iso.3.6.1.2.1.31.1.1.1.12.2":"0","iso.3.6.1.2.1.31.1.1.1.12.3":"0","iso.3.6.1.2.1.31.1.1.1.13.1":"0","iso.3.6.1.2.1.31.1.1.1.13.2":"0","iso.3.6.1.2.1.31.1.1.1.13.3":"0","iso.3.6.1.2.1.31.1.1.1.15.1":"10","iso.3.6.1.2.1.31.1.1.1.15.2":"1000","iso.3.6.1.2.1.31.1.1.1.15.3":"1000","iso.3.6.1.2.1.31.1.1.1.16.1":"2","iso.3.6.1.2.1.31.1.1.1.16.2":"2","iso.3.6.1.2.1.31.1.1.1.16.3":"2","iso.3.6.1.2.1.31.1.1.1.17.1":"2","iso.3.6.1.2.1.31.1.1.1.17.2":"1","iso.3.6.1.2.1.31.1.1.1.17.3":"1","iso.3.6.1.2.1.31.1.1.1.18.1":"","iso.3.6.1.2.1.31.1.1.1.18.2":"","iso.3.6.1.2.1.31.1.1.1.18.3":"","iso.3.6.1.2.1.31.1.1.1.19.1":"0","iso.3.6.1.2.1.31.1.1.1.19.2":"0","iso.3.6.1.2.1.31.1.1.1.19.3":"0"}';
$hostname="foo-bar.iwillfearnoevil.com";
$type='snmp';
$cycle='300';
$checkAction='1.3.6.1.2.1.31.1.1.1';
$checkName='ifName_64';
$test=sendMetricToRrd($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
echo "Test Results: (0 is a success) " . $test . "\n";
*/
/*
// Testing Drive space inserts
$checkName='driveSpace';
$checkAction='1.3.6.1.2.1.25.2.3.1';
$hostname='test.foo.bar.iwillfearnoevil.com';
$dataToBeInserted='{"iso.3.6.1.2.1.25.2.3.1.1.1":"1","iso.3.6.1.2.1.25.2.3.1.1.3":"3","iso.3.6.1.2.1.25.2.3.1.1.6":"6","iso.3.6.1.2.1.25.2.3.1.1.7":"7","iso.3.6.1.2.1.25.2.3.1.1.8":"8","iso.3.6.1.2.1.25.2.3.1.1.10":"10","iso.3.6.1.2.1.25.2.3.1.1.35":"35","iso.3.6.1.2.1.25.2.3.1.1.36":"36","iso.3.6.1.2.1.25.2.3.1.1.38":"38","iso.3.6.1.2.1.25.2.3.1.1.39":"39","iso.3.6.1.2.1.25.2.3.1.1.40":"40","iso.3.6.1.2.1.25.2.3.1.1.67":"67","iso.3.6.1.2.1.25.2.3.1.1.69":"69","iso.3.6.1.2.1.25.2.3.1.2.1":"iso.3.6.1.2.1.25.2.1.2","iso.3.6.1.2.1.25.2.3.1.2.3":"iso.3.6.1.2.1.25.2.1.3","iso.3.6.1.2.1.25.2.3.1.2.6":"iso.3.6.1.2.1.25.2.1.1","iso.3.6.1.2.1.25.2.3.1.2.7":"iso.3.6.1.2.1.25.2.1.1","iso.3.6.1.2.1.25.2.3.1.2.8":"iso.3.6.1.2.1.25.2.1.1","iso.3.6.1.2.1.25.2.3.1.2.10":"iso.3.6.1.2.1.25.2.1.3","iso.3.6.1.2.1.25.2.3.1.2.35":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.36":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.38":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.39":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.40":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.67":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.2.69":"iso.3.6.1.2.1.25.2.1.4","iso.3.6.1.2.1.25.2.3.1.3.1":"Physical memory","iso.3.6.1.2.1.25.2.3.1.3.3":"Virtual memory","iso.3.6.1.2.1.25.2.3.1.3.6":"Memory buffers","iso.3.6.1.2.1.25.2.3.1.3.7":"Cached memory","iso.3.6.1.2.1.25.2.3.1.3.8":"Shared memory","iso.3.6.1.2.1.25.2.3.1.3.10":"Swap space","iso.3.6.1.2.1.25.2.3.1.3.35":"\/run","iso.3.6.1.2.1.25.2.3.1.3.36":"\/","iso.3.6.1.2.1.25.2.3.1.3.38":"\/dev\/shm","iso.3.6.1.2.1.25.2.3.1.3.39":"\/run\/lock","iso.3.6.1.2.1.25.2.3.1.3.40":"\/sys\/fs\/cgroup","iso.3.6.1.2.1.25.2.3.1.3.67":"\/opt\/nasShare","iso.3.6.1.2.1.25.2.3.1.3.69":"\/run\/snapd\/ns","iso.3.6.1.2.1.25.2.3.1.4.1":"1024","iso.3.6.1.2.1.25.2.3.1.4.3":"1024","iso.3.6.1.2.1.25.2.3.1.4.6":"1024","iso.3.6.1.2.1.25.2.3.1.4.7":"1024","iso.3.6.1.2.1.25.2.3.1.4.8":"1024","iso.3.6.1.2.1.25.2.3.1.4.10":"1024","iso.3.6.1.2.1.25.2.3.1.4.35":"4096","iso.3.6.1.2.1.25.2.3.1.4.36":"4096","iso.3.6.1.2.1.25.2.3.1.4.38":"4096","iso.3.6.1.2.1.25.2.3.1.4.39":"4096","iso.3.6.1.2.1.25.2.3.1.4.40":"4096","iso.3.6.1.2.1.25.2.3.1.4.67":"16384","iso.3.6.1.2.1.25.2.3.1.4.69":"4096","iso.3.6.1.2.1.25.2.3.1.5.1":"57686460","iso.3.6.1.2.1.25.2.3.1.5.3":"66075064","iso.3.6.1.2.1.25.2.3.1.5.6":"57686460","iso.3.6.1.2.1.25.2.3.1.5.7":"55389296","iso.3.6.1.2.1.25.2.3.1.5.8":"1476","iso.3.6.1.2.1.25.2.3.1.5.10":"8388604","iso.3.6.1.2.1.25.2.3.1.5.35":"1442162","iso.3.6.1.2.1.25.2.3.1.5.36":"15415243","iso.3.6.1.2.1.25.2.3.1.5.38":"7210807","iso.3.6.1.2.1.25.2.3.1.5.39":"1280","iso.3.6.1.2.1.25.2.3.1.5.40":"7210807","iso.3.6.1.2.1.25.2.3.1.5.67":"1274937979","iso.3.6.1.2.1.25.2.3.1.5.69":"1442162","iso.3.6.1.2.1.25.2.3.1.6.1":"57384648","iso.3.6.1.2.1.25.2.3.1.6.3":"57397024","iso.3.6.1.2.1.25.2.3.1.6.6":"155264","iso.3.6.1.2.1.25.2.3.1.6.7":"55389296","iso.3.6.1.2.1.25.2.3.1.6.8":"1476","iso.3.6.1.2.1.25.2.3.1.6.10":"12376","iso.3.6.1.2.1.25.2.3.1.6.35":"398","iso.3.6.1.2.1.25.2.3.1.6.36":"3673198","iso.3.6.1.2.1.25.2.3.1.6.38":"0","iso.3.6.1.2.1.25.2.3.1.6.39":"0","iso.3.6.1.2.1.25.2.3.1.6.40":"0","iso.3.6.1.2.1.25.2.3.1.6.67":"1030763271","iso.3.6.1.2.1.25.2.3.1.6.69":"398"}';
$type='snmp';
$cycle=300;
sendMetricToRrd($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
*/

/*
// Testing lm-sensors
$dataToBeInserted='{"iso.3.6.1.4.1.2021.13.16.2.1.1.13":" 13","iso.3.6.1.4.1.2021.13.16.2.1.1.14":" 14","iso.3.6.1.4.1.2021.13.16.2.1.1.15":" 15","iso.3.6.1.4.1.2021.13.16.2.1.1.16":" 16","iso.3.6.1.4.1.2021.13.16.2.1.2.13":" temp1","iso.3.6.1.4.1.2021.13.16.2.1.2.14":" temp2","iso.3.6.1.4.1.2021.13.16.2.1.2.15":" temp3","iso.3.6.1.4.1.2021.13.16.2.1.2.16":" k10temp-pci-00c3:temp1","iso.3.6.1.4.1.2021.13.16.2.1.3.13":" 34000","iso.3.6.1.4.1.2021.13.16.2.1.3.14":" 34000","iso.3.6.1.4.1.2021.13.16.2.1.3.15":" 4294839296","iso.3.6.1.4.1.2021.13.16.2.1.3.16":" 20250","iso.3.6.1.4.1.2021.13.16.3.1.1.10":" 10","iso.3.6.1.4.1.2021.13.16.3.1.1.11":" 11","iso.3.6.1.4.1.2021.13.16.3.1.1.12":" 12","iso.3.6.1.4.1.2021.13.16.3.1.2.10":" fan1","iso.3.6.1.4.1.2021.13.16.3.1.2.11":" fan2","iso.3.6.1.4.1.2021.13.16.3.1.2.12":" fan3","iso.3.6.1.4.1.2021.13.16.3.1.3.10":" 2973","iso.3.6.1.4.1.2021.13.16.3.1.3.11":" 1167","iso.3.6.1.4.1.2021.13.16.3.1.3.12":" 1285","iso.3.6.1.4.1.2021.13.16.4.1.1.1":" 1","iso.3.6.1.4.1.2021.13.16.4.1.1.2":" 2","iso.3.6.1.4.1.2021.13.16.4.1.1.3":" 3","iso.3.6.1.4.1.2021.13.16.4.1.1.4":" 4","iso.3.6.1.4.1.2021.13.16.4.1.1.5":" 5","iso.3.6.1.4.1.2021.13.16.4.1.1.6":" 6","iso.3.6.1.4.1.2021.13.16.4.1.1.7":" 7","iso.3.6.1.4.1.2021.13.16.4.1.1.8":" 8","iso.3.6.1.4.1.2021.13.16.4.1.1.9":" 9","iso.3.6.1.4.1.2021.13.16.4.1.2.1":" in0","iso.3.6.1.4.1.2021.13.16.4.1.2.2":" in1","iso.3.6.1.4.1.2021.13.16.4.1.2.3":" in2","iso.3.6.1.4.1.2021.13.16.4.1.2.4":" +3.3V","iso.3.6.1.4.1.2021.13.16.4.1.2.5":" in4","iso.3.6.1.4.1.2021.13.16.4.1.2.6":" in5","iso.3.6.1.4.1.2021.13.16.4.1.2.7":" in6","iso.3.6.1.4.1.2021.13.16.4.1.2.8":" 3VSB","iso.3.6.1.4.1.2021.13.16.4.1.2.9":" Vbat","iso.3.6.1.4.1.2021.13.16.4.1.3.1":" 2760","iso.3.6.1.4.1.2021.13.16.4.1.3.2":" 2736","iso.3.6.1.4.1.2021.13.16.4.1.3.3":" 1188","iso.3.6.1.4.1.2021.13.16.4.1.3.4":" 3264","iso.3.6.1.4.1.2021.13.16.4.1.3.5":" 36","iso.3.6.1.4.1.2021.13.16.4.1.3.6":" 2508","iso.3.6.1.4.1.2021.13.16.4.1.3.7":" 2604","iso.3.6.1.4.1.2021.13.16.4.1.3.8":" 192","iso.3.6.1.4.1.2021.13.16.4.1.3.9":" 3360"}';
//$dataToBeInserted="[\".1.3.6.1.4.1.2021.13.16.2.1.1.13 : 13\",\".1.3.6.1.4.1.2021.13.16.2.1.1.14 : 14\",\".1.3.6.1.4.1.2021.13.16.2.1.1.15 : 15\",\".1.3.6.1.4.1.2021.13.16.2.1.1.16 : 16\",\".1.3.6.1.4.1.2021.13.16.2.1.2.13 : temp1\",\".1.3.6.1.4.1.2021.13.16.2.1.2.14 : temp2\",\".1.3.6.1.4.1.2021.13.16.2.1.2.15 : temp3\",\".1.3.6.1.4.1.2021.13.16.2.1.2.16 : k10temp-pci-00c3:temp1\",\".1.3.6.1.4.1.2021.13.16.2.1.3.13 : 34000\",\".1.3.6.1.4.1.2021.13.16.2.1.3.14 : 34000\",\".1.3.6.1.4.1.2021.13.16.2.1.3.15 : 4294839296\",\".1.3.6.1.4.1.2021.13.16.2.1.3.16 : 15500\",\".1.3.6.1.4.1.2021.13.16.3.1.1.10 : 10\",\".1.3.6.1.4.1.2021.13.16.3.1.1.11 : 11\",\".1.3.6.1.4.1.2021.13.16.3.1.1.12 : 12\",\".1.3.6.1.4.1.2021.13.16.3.1.2.10 : fan1\",\".1.3.6.1.4.1.2021.13.16.3.1.2.11 : fan2\",\".1.3.6.1.4.1.2021.13.16.3.1.2.12 : fan3\",\".1.3.6.1.4.1.2021.13.16.3.1.3.10 : 2973\",\".1.3.6.1.4.1.2021.13.16.3.1.3.11 : 1165\",\".1.3.6.1.4.1.2021.13.16.3.1.3.12 : 1285\",\".1.3.6.1.4.1.2021.13.16.4.1.1.1 : 1\",\".1.3.6.1.4.1.2021.13.16.4.1.1.2 : 2\",\".1.3.6.1.4.1.2021.13.16.4.1.1.3 : 3\",\".1.3.6.1.4.1.2021.13.16.4.1.1.4 : 4\",\".1.3.6.1.4.1.2021.13.16.4.1.1.5 : 5\",\".1.3.6.1.4.1.2021.13.16.4.1.1.6 : 6\",\".1.3.6.1.4.1.2021.13.16.4.1.1.7 : 7\",\".1.3.6.1.4.1.2021.13.16.4.1.1.8 : 8\",\".1.3.6.1.4.1.2021.13.16.4.1.1.9 : 9\",\".1.3.6.1.4.1.2021.13.16.4.1.2.1 : in0\",\".1.3.6.1.4.1.2021.13.16.4.1.2.2 : in1\",\".1.3.6.1.4.1.2021.13.16.4.1.2.3 : in2\",\".1.3.6.1.4.1.2021.13.16.4.1.2.4 : +3.3V\",\".1.3.6.1.4.1.2021.13.16.4.1.2.5 : in4\",\".1.3.6.1.4.1.2021.13.16.4.1.2.6 : in5\",\".1.3.6.1.4.1.2021.13.16.4.1.2.7 : in6\",\".1.3.6.1.4.1.2021.13.16.4.1.2.8 : 3VSB\",\".1.3.6.1.4.1.2021.13.16.4.1.2.9 : Vbat\",\".1.3.6.1.4.1.2021.13.16.4.1.3.1 : 2760\",\".1.3.6.1.4.1.2021.13.16.4.1.3.2 : 2736\",\".1.3.6.1.4.1.2021.13.16.4.1.3.3 : 948\",\".1.3.6.1.4.1.2021.13.16.4.1.3.4 : 3288\",\".1.3.6.1.4.1.2021.13.16.4.1.3.5 : 36\",\".1.3.6.1.4.1.2021.13.16.4.1.3.6 : 2508\",\".1.3.6.1.4.1.2021.13.16.4.1.3.7 : 2604\",\".1.3.6.1.4.1.2021.13.16.4.1.3.8 : 192\",\".1.3.6.1.4.1.2021.13.16.4.1.3.9 : 3360\"]";
$hostname='guyver-office.iwillfearnoevil.com';
$checkAction="1.3.6.1.4.1.2021.13.16";
$checkName='lm-sensors';
$type='snmp';
$cycle=300;
sendMetricToRrd($hostname, $dataToBeInserted, $checkName, $checkAction, $type, $cycle);
*/

$type='nrpe';
$cycle=3000;
$checkName="check_zombie_procs";
$hostname='test.foo.bar.iwillfearnoevil.com';
$metric = "[\"PROCS OK: 0 processes with STATE = Z | procs=0;5;10;0;\"]";

//$metric="[\"TCP OK - 0.000 second response time on localhost port 22|time=0.000228s;;;0.000000;10.000000\"]";
//$metric="[\"DRIVE OK - 0.000 second response time on localhost port 22| /=123456KB\"]";
echo "sending metric " . $metric . "\n";
echo "SENT TO FUNCTION\n";
$checkAction = null;
$res=sendMetricToGraphite2($hostname, json_encode($metric,1), $checkName, $checkAction , $type, $cycle);





//$res=sendMetricToGraphite($hostname, $metric, $type);
echo "RESULT from metric push: 0(success) or somthing else, likely 1(failure) " . $res . "\n";
//$test=json_decode($dataToBeInserted1, true);
$test=json_decode($res, true);
echo "VALIDATE INPUT \n";
print_r($test, true);
var_dump($res);

?>
