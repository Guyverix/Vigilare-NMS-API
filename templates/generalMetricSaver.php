<?php
/*
  Method (function) definitions for all currently supported types of Metric Storage available
  This will be the start of calling the classes, and leveraging them to get the objects returned.
  Likely a real developer would do this differently, but meh.  It makes sense to me.

  Return codes are based on the step in the process.  This will allow for debugging when something chokes.
  Return 1 main method
  Return 2 class method
  Return 3 template script
  Return 4 back to main method after template completed
  Return 5 actual update with data
*/

//require __DIR__ . "/../app/Logger.php";
require_once __DIR__ . "/generalMetricClass.php";
require_once __DIR__ . '/../src/Infrastructure/Shared/Functions/daemonFunctions.php';
if ( ! isset($logger)) {
  global $logger;
}

// Afer RRD I suspect this will be the next most common metric storage type
function sendMetricToGraphite($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  $logger->debug("Attempting to send metric to graphtie for " . $hostname . " and service check " . $checkName);

  if (! isset($metricData)) {
    $metricData = new MetricParsingGraphite();
  }

  if ( null === $checkAction ) {
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, null, $type);
  }
  else {
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction, $type);
  }

  if ( $noise !== 0) {
    return $noise;
  }

  $returnArray = $metricData->returnArrayValues;

  if ( ! is_array($returnArray) && $type !== "nrpe" ) {
    return "template did not return an array with data: " . $dataToBeInserted;
  }
  elseif ($returnArray == 1) {
    return "template result is invalid for supplied parameters";
  }

  if (! isset($graphitePush)) {
    // This is going to have to get cleaned and or removed
    require_once __DIR__ . '/../app/Graphite.php';
    $graphitePush = new Graphite();
  }

  foreach ($returnArray as $k => $v) {
    $logger->debug("generalMetricSaver push " . $k . " value " . $v);
    $graphitePushNoise = $graphitePush->testMetric( $k, $v);
    $logger->debug("generalMetricSaver result " . json_encode($graphitePushNoise,1));
  }
  unset ($returnArray);
  unset ($graphitePush);
  unset ($noise);
  return 0;
}

// Helper function for sendMetricToRrd
function rrdExist($singleRrdFileName, $cycle, $rrdCreateDs) {
  global $logger;
  // if ( ! isset($logger)) { echo "LOGGER is not set\n"; }
  $fileName = __DIR__ . "/../rrd/" . $singleRrdFileName;
  if ( file_exists($fileName)) {
    $logger->debug("Found filename " . $singleRrdFileName);
    return 0;
  }
  else {
    $pathParts = pathinfo($fileName);
    if ( ! file_exists($pathParts['dirname'])) {
      mkdir($pathParts['dirname'],0777, true);
      $logger->debug("Created directory " . $pathParts['dirname']);
    }

    if (is_null($cycle)) {
      $findCycle = explode('-',$checkName);
      $cycle = $findCycle[1];
      $logger->warning("Attempting to find cycle time.  Guessing " . $cycle);
    }

    if ( $cycle == 60) {
      $retention="RRA:AVERAGE:0.5:1:288 RRA:AVERAGE:0.5:3:672 RRA:AVERAGE:0.5:12:744 RRA:AVERAGE:0.5:72:1460";
    }
    else {
      // Old observium example observium/includes/defaults.inc.php
      //                             7 days of 5 min         62 days of 30 min       120 days of 2 hour       4 years of 1 day
      // $config['rrd']['rra']  = "RRA:AVERAGE:0.5:1:2016  RRA:AVERAGE:0.5:6:2976  RRA:AVERAGE:0.5:24:1440  RRA:AVERAGE:0.5:288:1440 ";
      // $config['rrd']['rra'] .= "                         RRA:MIN:0.5:6:1440      RRA:MIN:0.5:96:360       RRA:MIN:0.5:288:1440 ";
      // $config['rrd']['rra'] .= "                         RRA:MAX:0.5:6:1440      RRA:MAX:0.5:96:360       RRA:MAX:0.5:288:1440 ";
      $retention="RRA:AVERAGE:0.5:1:2016 RRA:AVERAGE:0.5:6:2976 RRA:AVERAGE:0.5:24:1440 RRA:AVERAGE:0.5:288:1440 RRA:MIN:0.5:6:1440 RRA:MIN:0.5:96:360 RRA:MIN:0.5:288:1440 RRA:MAX:0.5:6:1440 RRA:MAX:0.5:96:360 RRA:MAX:0.5:288:1440 ";
      // $retention="RRA:AVERAGE:0.5:1:288 RRA:AVERAGE:0.5:3:672 RRA:AVERAGE:0.5:12:744 RRA:AVERAGE:0.5:72:1460   RRA:MIN:0.5:6:1460 RRA:MIN:0.5:96:360 RRA:MIN:0.5:72:1460  RRA:MAX:0.5:6:1460 RRA:MAX:0.5:96:360 RRA:MAX:0.5:72:1460";
    }

    $cmd="rrdtool create " . $fileName . " --step " . $cycle .  " " . $rrdCreateDs . " " . $retention . " ";

    $result = exec($cmd, $output, $exitCode);
    if ( $exitCode == 0 ) {
      $logger->info("Execute rrdtool create output: "  . $output . " command " . $cmd . " exit code " . $exitCode . " result(array) " . json_encode($result,1) . " filename " . $fileName . " step " . $cycle  . " Create DS " . $rrdCreateDs . " retention policy " . $retention );
      return 0;
    }
    else {
      $logger->error("Failed to create RRD database file " . json_encode($result,1));
      return "Failed to create RRD database file " . json_encode($result,1);
    }
  }
}

// Helper function for sendMetricToRrd
function rrdUpdate($singleRrdMetricFileName, $singleRrdMetricUpdate) {
  global $logger;
  $fileName = __DIR__ . "/../rrd/" . $singleRrdMetricFileName;
  $cmd="rrdtool update " . $fileName . " " . $singleRrdMetricUpdate . " ";
  $result = exec($cmd, $output, $exitCode);
  if ( $exitCode == 0 ) {
    $logger->debug("Attempting rrdtool update. result(json): " . json_encode($result,1) . " filename: " . $singleRrdMetricFileName . " metric " . $singleRrdMetricUpdate);
    return 0;
  }
  else {
    $logger->error("Failed to update RRD database file " . json_encode($result,1));
    return "Failed to update RRD database file " . json_encode($result,1);
  }
}

// Likely the most common call for metric storage
// We are not going to need to pass checkAction to the object
function sendMetricToRrd($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  $checkName = preg_replace('/[ .,]/','', $checkName);

  if (! isset($metricData)) {
    $metricData = new MetricParsingRrd();
    $logger->debug("Attempt to create new MetricParsingRrd object for checkName " . $checkName . " on host " . $hostname);
  }

  if ( $type == "poller" ) {
    if (is_null($cycle)) {
      $findCycle=explode('-',$checkName);
      $cycle=$findCycle[1];
    }
  }

  if ( is_null($type)) {
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName,$checkAction, null, $cycle );
  }
  else {
    $noise = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction , $type, $cycle);
  }

  if ( $noise == 1) {
    $logger->error("parseRaw method failed to work with parameters supplied: " . json_encode($noise,1));
    return "parseRaw method failed to work with parameters supplied";
  }

  $returnArray = $metricData->returnArrayValues;


  if ( ! is_array($returnArray)) {
    return "Template did not return an array from data: " . $dataToBeInserted . " Returned " . $returnArray;
  }
  elseif ($returnArray == 1) {
    return "Template exited in error of some kind";
  }

  foreach ($returnArray as $singleRrd) {
    $dbSetup = rrdExist($singleRrd['fileName'],$cycle, $singleRrd['create'] );
    if ($dbSetup !== 0) {
      return "Unable to find an existing RRD database file or create one: " . $dbSetup . " " . $singleRrd['fileName'];
    }
  }

  foreach ($returnArray as $singleRrdMetrics) {
    $dbUpdate=rrdUpdate($singleRrdMetrics['fileName'], $singleRrdMetrics['update']);
    if ($dbUpdate !== 0) {
      return "rrdUpdate failed " . $dbUpdate;
    }
  }
  unset ($returnArray);
  unset ($metricData->returnArrayValues);
  return 0;
}

// Likely used for slow iteration cycle things to store that will be pulled commonly but not evented on. IE portsUsed
function sendMetricToDatabase($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  if (! isset($metricData)) {
    $metricData = new MetricParsingDatabase();
  }
  $result=$metricData->parseRaw($host, $dataToBeInserted, $checkName, $checkAction);
  if ( $result !== 0) { return $result; }

  $returnArray = $metricData->returnArrayValues;
  if (empty($returnArray)) { return "Array from template was empty"; }

  $returnArray=json_encode($returnArray,1);
  $result=sendPerformanceDatabase($hostname, $checkName, $returnArray);

  unset ($returnArray);
  unset ($metricData->returnArrayValues);
  if ( $result !== 0) { return $result; }
  return 0;
}

// Likely this will be little used.  Storing metrics into a file is kinda useless with databases available
function sendMetricToFile($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  if (! isset($metricData)) {
    $metricData=new MetricParsingFile();
  }

  $result = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  if ( $result !== 0 ) { return $result; }

  $fileName = __DIR__ . "/../file/" . $hostname . "/" . $checkName . ".txt";
  file_put_contents($fileName, $dataToBeInserted);

  $logger->debug("Created or overwrote text drop file " . $fileName);
  unset ($returnArray);
  unset ($metricData->returnArrayValues);
  return 0;
}

// Basically a var dump to file specific to metrics attempting to be inserted into a storage type
function sendMetricToDebugger($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  if (! isset($metricData)) {
    $metricData=new MetricParsingFileDebugger();
  }
  $result = $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);
  if ( $result !== 0 ) { return $result; }

  $fileName = __DIR__ . "/../file/" . $hostname . "/debugger/" . $checkName . ".txt";
  $allData=array('hostname' =>$hostname, 'dataToBeInserted' => $dataToBeInserted, 'checkName' => $checkName, 'checkAction' => $checkAction, 'type' => $type, 'cycle' => $cycle);
  file_put_contents($fileName, json_encode($allData,1));
  unset ($metricData);
  $logger->debug("Debugging file created or overwritten " . $fileName);
  return 0;
}


/* TODO if actually needed in the future */
function sendMetricToInflux($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
  global $logger;
  $logger->error("Someone attempted to insert to influxdb even though the code does not exist yet for checkName " . $checkName);
  return "InfluxDb is not even designed yet.  Please play again";
}

/* Any other storage types that will be needed in the future go here */

// As this is a live system that can insert data, it is discouraged to have tests in here.
// That is what the manualTestingMetricSaver.php is for.
?>
