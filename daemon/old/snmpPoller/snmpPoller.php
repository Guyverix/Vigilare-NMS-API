<?php
declare(strict_types=1);

// Not a smart solution, however snmp failures err to stdout even though we are dealing with
// them internally
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
// https://alexwebdevelop.com/php-daemons/

// unique pidfile based on iteration cycle so we can kill easier
$pid=getmypid();

// Support daemon shutdown
pcntl_async_signals(true);
pcntl_signal(SIGTERM, 'signalHandler'); // Termination (kill was called)
pcntl_signal(SIGHUP, 'signalHandler');  // Terminal log-out
pcntl_signal(SIGINT, 'signalHandler');  // Interrupted (Ctrl-C is pressed) (when in foreground)

/*
This is intended to be called via PHP-cli so we need to support args
iterationCycle is critical, but we also need to support start /stop as well.
*/

$cliOptions= getopt("i:s:");
if (isset($cliOptions['i'])) {
  $iterationCycle=intval($cliOptions['i']);
}
if (isset($cliOptions['s'])) {
  $daemonState=$cliOptions['s'];
}
if (empty($iterationCycle)) {
  $iterationCycle=3;
}
if (empty($daemonState)) {
  $daemonState='start';
}

// Enable logging system (filename, and minimum sev to log, iterationCycle)
require __DIR__ . '/../../app/Logger.php';
$logger = new Logger(basename(__FILE__), 0, $iterationCycle);

// Enable Metrics logging in Graphite
require __DIR__ . '/../../app/Graphite.php';
//$metrics = new Graphite( "nms" , "true");
$metrics = new Graphite();

// Enable Eventing support for daemon
require __DIR__ . '/../../app/Curl.php';

// Start the guts of the daemon here
$sleepDate=time();
date_default_timezone_set('UTC');
$logger->info("Daemon called for iteration cycle of $iterationCycle under pid: $pid to $daemonState daemon");

// Get a database object built
require __DIR__ . '/../../app/Database.php';
$db = new Database();

/*
// Debugging database object
var_dump($db);
print_r($db);
echo $db->error;
*/

function sendAlarm ( ?string $alarmEventSummary = "Oops.  Someone forgot to set the event summary for information", ?int $alarmEventSeverity = 2, ?string $alarmEventName = "snmpPoller" ) {
  $alarm = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  $alarmInfo=array( "device" => "larvel01.iwillfearnoevil.com", "eventSummary" => "" , "eventName" => "", "eventSeverity" => 0 );
  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo['eventSummary']=$alarmEventSummary;
  $alarmInfo["eventSeverity"]=$alarmEventSeverity;
  $alarmInfo["eventName"]=$alarmEventName;
  // Set our details here
  $alarm->data($alarmInfo);
  $alarm->send();
  $alarm->close();
  // No need to keep this in RAM, as object should be rarely used
  unset ($alarm);
}

function sendHostAlarm ( $alarmEventSummary, $alarmEventSeverity , $alarmEventName , $hostname ) {
  $alarm2 = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  $alarmInfo2=array( "device" => "undefined", "eventSummary" => "" , "eventName" => "", "eventSeverity" => 0 );

  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo2['device'] = $hostname;
  $alarmInfo2['eventSummary']=$alarmEventSummary;
  $alarmInfo2["eventSeverity"]=$alarmEventSeverity;
  $alarmInfo2["eventName"]=$alarmEventName;

  // Set our details here
  $alarm2->data($alarmInfo2);
  $alarm2->send();
  $alarm2->close();
  // No need to keep this in RAM, as object should be rarely used
  unset ($alarm2);
}

// This will allow different daemons with different
// iteration cycles to run side by side
$pidFileName = basename(__FILE__) . '.' . $iterationCycle . '.pid';
$pidFile = @fopen($pidFileName, 'c');
if (! $pidFile) {
//  sendAlarm("Unable to open pidfile $pidFileName", 3);
  die("Could not open $pidFileName\n");
}

if (!@flock($pidFile, LOCK_EX | LOCK_NB)) {
  $pid= file_get_contents($pidFileName);
  if ( $daemonState == "stop" ) {
    echo "Stopping daemon " . basename(__FILE__) . " pid " . $pid . "\n";
    $dbKill = new Database();
    $dbKill->query("DELETE FROM heartbeat WHERE device=\"poller\" AND component=\"iteration_$iterationCycle\" ");
    $dbKill->execute();
    exec ("kill -15 $pid &>/dev/null");
    die();
  }
  else {
    die("Daemon already running for " . basename(__FILE__) . " pid: " . $pid . "\n");
  }
}
elseif ( $daemonState == "stop" ) {
  ftruncate($pidFile, 0);
  die("Daemon does not have a recorded pid running for " . basename(__FILE__) . "\n");
}
else {
  $logger->warning("Daemon start was called but daemon is already running " . basename(__FILE__) );
}
echo "Starting daemon " . basename(__FILE__) . " pid " . $pid . "\n";
// Log our running pid value now
ftruncate($pidFile, 0);
fwrite($pidFile, "$pid");

/*
This is what we need to get from the database.
$arr=["1.3.6.1.2.1.1.6.0", "public" , "2c", "192.168.15.58"];
*/


// This is our daemon loop
while (true) {
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      sendAlarm("Daemon has lost its database connection", 5, "snmpPoller-database-" . $iterationCycle);
      unset($db);
      $logger->error("Database failure $this->error");
      sleep(20);
      $db = new Database();
    }
    sendAlarm("Daemon has restored its database connection", 0, "snmpPoller-database-" . $iterationCycle);
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $utcDate=gmdate("Y-m-d H:i:s");
  $db->query("INSERT INTO heartbeat VALUES(\"poller\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$pid\" ");
  $db->execute();
  $logger->debug("Heartbeat sent");


  /*
    Pull in oids that match our iteration cycle (limiter) and lastUpdate is older than (now - $iterationCycle)
    Also pulling type of snmp get as well as where we store it at
  */
  $db->query("SELECT oid, hostname, type, storage FROM snmpPoller WHERE iteration=$iterationCycle AND lastRun <= NOW() - INTERVAL $iterationCycle SECOND");
  $db->execute();
  $snmpOidCount=$db->rowCount();
  $snmpOids=$db->resultset();
  $logger->info("snmpPoller table query for oids to poll returned $snmpOidCount rows");

  // Update the database timestamp before we get into the iteration loops
  foreach( $snmpOids as $updateOids ) {
    $id='';
    $oid='';
    foreach ($updateOids as $k => $v ) {
    // echo "updateOids $k => $v.\n";
      if ($k == 'oid') {
       $oid=$v;
      }
      if ($k == 'hostname') {
       $id=$v;
      }
      if (!empty($id) AND !empty($oid)) {
       $db->query("UPDATE snmpPoller SET lastRun=now() WHERE oid=\"$oid\" AND iteration=$iterationCycle AND hostname=$id");
       $pollerUpdate=$db->execute();
      }
    }
  }

  $snmpHosts=[];
  foreach($snmpOids as $snmpOid) {
    foreach($snmpOid as $oid => $id) {
      // pull in hosts that will use oids (filter)
        echo "\$snmpOid[$oid] => $id\n";  // DEBUG LOOP
      if ( $oid == 'oid' ) {
        $oidValue = $id ;
      }
      if ( $oid == 'type' ) {
        $type = "$id";
      }
      if ( $oid == 'storage' ) {
        $storage = "$id";
      }
      if ( $oid == 'hostname' ) {
        $db->query("SELECT h.hostname, h.address, a.value AS snmpSetup FROM host h INNER JOIN hostAttribute a ON h.hostname = a.hostname WHERE h.id=$id AND a.hostname = h.hostname AND a.name = 'snmpSetup'");
        $result= json_decode(json_encode($db->resultset()), true);
      }
      }
      if (! empty($result)) {
      $result[0]["oid"] = "$oidValue" ;
      $result[0]["type"] = "$type";
      $result[0]["storage"] = "$storage";
      $snmpHosts[] = $result[0];
    }
  }
  // $snmpHosts[] contains array of: hostname, address, oid, (string of community, version, port) OID wants to query
  //print_r($result);
  //print_r($snmpHosts);

  // Get details for snmp values on a per host basis
  // add them to the array matching host
  foreach ($snmpHosts as $snmpHost) {
    foreach ($snmpHost as $k => $v) {
      if ($k = "snmpSetup" ) {
        $snmpSetup=(explode(",", $snmpHost[$k]));
        $snmpCommunity=$snmpSetup[0];
        $snmpVer=$snmpSetup[1];
        $snmpPort=$snmpSetup[2];
      }
      if ($k = "address" ){
        $snmpAddress=$snmpHost[$k];
      }
     if ($k = "hostname" ){
        $snmpHostname=$snmpHost[$k];
      }
      if ($k = "oid") {
        $snmpOidValue=$snmpHost[$k];
      }
      if ($k = "type" ) {
        $snmpType=$snmpHost[$k];
      }
      if ($k = "storage" ) {
        $snmpStorage=$snmpHost[$k];
      }
    }
    // Set our SNMP version to exactly what is expected
    // We do NOT currently support SNMP v3 right now
    $snmpVer=preg_replace('/\D/', '', $snmpVer);
    $snmpVersion=intval($snmpVer);
    // echo "Checking parsed SNMP Version " . $snmpVer . " mapped to \"" . $snmpVersion . "\"\n";
    $logger->debug("Checking parsed SNMP Version " . $snmpVer . " mapped to " . $snmpVersion);


    // Now build out our queries here!
    $logger->debug("SNMP Version " . $snmpVersion . " SNMP ADDRESS " . $snmpAddress . " SNMP COMMUNITY STRING " . $snmpCommunity);
    if ( $snmpVersion != 2) {
      $session = new SNMP($snmpVersion, "$snmpAddress", "$snmpCommunity");
    }
    else {
      $session = new SNMP(SNMP::VERSION_2c, "$snmpAddress", "$snmpCommunity");
    }
    if ( $snmpType == "get" ){
      $session->enum_print =0 ;
      $session->valueretrieval = SNMP_VALUE_PLAIN;
      $session->oid_output_format = SNMP_OID_OUTPUT_SUFFIX;
      $sessionResult = $session->get("$snmpOidValue");
      $sessionResult =  $sessionResult ;
    }
    elseif ( $snmpType = "walk" ){
      // Remember walks will return arrays!
      $session->enum_print =0 ;
      // $session->valueretrieval = SNMP_VALUE_PLAIN; // THIS CAUSES ISSUES WITH hex-STRING returns
      $session->oid_output_format = SNMP_OID_OUTPUT_SUFFIX;
      $session->exceptions_enabled = 1;
      $sessionResult1 = $session->walk("$snmpOidValue");
      foreach ($sessionResult1 as $k => $v) {
        // Clean up our returns to now show the type of return data
        $v=preg_replace('/.*.: /','',$v);
        $sessionResult1[$k] = $v;
      }
      $sessionResult =  json_encode($sessionResult1);
    }
    else {
      $logger->warning("snmpType has not been set correctly.  PHP only supports get or walk values with the built in SNMP class");
    }
    $logger->debug("Loop retrieved for " . $snmpType . ' ' . $snmpAddress . ' ' . $snmpOidValue .' ' . $sessionResult);

    print_r($session);
    echo "Session Error Numeric " . $session->getErrno() . "\n";
    echo "Session Error String " . $session->getError() . "\n";
    echo "SessionResult json_encoded " . $sessionResult3 . "\n";


    // Single gets (USUALLY) strings should go in the database
    if (!empty($sessionResult)) {
      if ( $snmpStorage == "database" ) {
       // escape out our " characters!  Disalbed as we are using bind and prepare for adhoc strings
       // $sessionResult=str_replace('"','\"', $sessionResult);
//       $db->query("INSERT INTO performance VALUES(\"$snmpHostname\", \"$snmpOidValue\",now(), :sessionResult) ON DUPLICATE KEY UPDATE  date=now(), value= :sessionResult ");
       $db->query("INSERT INTO performance VALUES(\"$snmpHostname\", \"$snmpOidValue\", now(), :sessionResult) ON DUPLICATE KEY UPDATE date=NOW(), value= :sessionResult ");
       $pollerUpdate=$db->bind('sessionResult', "$sessionResult");
       $pollerUpdate=$db->execute();
/*
       if(! empty ($db->error)) {
         $logger->error("MySQL Insert error for performance VALUES: " . $db->error);
       }
       else {
         $logger->info("MySQL Insert complete for performance VALUES: " . $db->stmt );
       }
*/
      }
      elseif ($snmpStorage == "databaseMetric") {
        if (file_exists(__DIR__ . "/../../templates/sendMetricToDatabase.php")) {
          require_once __DIR__ . "/../../templates/sendMetricToDatabase.php";
          $sendMetricDatabase=sendMetricToDatabase( $snmpHostname, $sessionResult, $snmpOidValue);
           $logger->debug("Session result data " . $sessionResult);
          if ($sendMetricDatabase == 0) {
            $logger->info("Performance database push complete for " . $snmpHostname . " updating oid " . $snmpOidValue);
          }
          else {
            $logger->error("Performance database push failed for " . $snmpHostname . " updating oid " . $snmpOidValue);
            sendAlarm("Performance database push failed", 2, "snmpPoller-queryPush-" . $iterationCycle);
          }
        }
        else {
          $logger->error( "Unable to find root template file /templates/sendMetricToDatabase.php");
          sendAlarm("Unable to find template file sendMetricToDatabase.php", 2, "snmpPoller-queryPush-" . $iterationCycle);
        }
      }
      elseif ( $snmpStorage == "graphite" ) {
        if (file_exists(__DIR__ . "/../../templates/sendMetricToGraphite.php")) {
          require_once __DIR__ . "/../../templates/sendMetricToGraphite.php";
          $sendMetricGraphite=sendMetricToGraphite( $snmpHostname, $sessionResult, $snmpOidValue);
          if ($sendMetricGraphite == 0) {
            $logger->info("Metric push to Graphite complete for " . $snmpHostname . " updating oid " . $snmpOidValue);
          }
          else {
            $logger->error("Metric push to Graphite failed for " . $snmpHostname . " updating oid " . $snmpOidValue);
            sendAlarm("Metric push to Graphite failed", 2, "snmpPoller-queryPush-" . $iterationCycle);
          }
        }
        else {
          $logger->error( "Unable to find root template file /templates/sendMetricToGraphite.php");
          sendAlarm("Unable to find template file sendMetricToGraphite.php", 2, "snmpPoller-queryPush-" . $iterationCycle);
        }
      }
      elseif ( $snmpStorage == "rrd" ) {
        $rrd->update($snmpOidValue, $sessionResult);
      }
      else {
        $logger->error("snmpStorage was not set correctly.  Supported storage database, graphite, rrd not: " . $snmpStorage );
      }
    }
    else {
      sendHostAlarm("Error " . $session->getError() . " for " . $snmpHostname . " using oid " . $snmpOidValue, 4, $snmpOidValue , $snmpHostname);
      // sendAlarm("snmp query returned error " . $session->getError() . " for " . $snmpHostname . " using oid " . $snmpOidValue, 3, "snmpPoller-queryIteration-" . $iterationCycle);
      $logger->error("snmp query returned error for " . $snmpHostname . " message " . $session->getError());
    }
  }

  // we have completed our iteration for this cycle, now update info
  // about the daemon itself
  $timeNow=time();
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    sendAlarm("Daemon poller had an iteration overrun.  Confirm daemon is not overloaded in logs delta is " . $timeDelta . " seconds" , 2, "snmpPoller-iterationComplete-" . $iterationCycle);
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
    sendAlarm("Daemon poller iteration complete.  Daemon is not overloaded in logs delta is " . $timeDelta . " seconds", 0, "snmpPoller-iterationComplete-" . $iterationCycle);
    $logger->info("Iteration complete.  Cycle took $timeDelta seconds to complete");
    $newIterationCycle=( $iterationCycle - $timeDelta );
  }
  sleep($newIterationCycle);
  $sleepDate=time();
}

// This will quit the daemon when a signal is received
function signalHandler($signal) {
  global $iterationCycle;
  $logger2 = new Logger(basename(__FILE__), 0, $iterationCycle);
  $logger2->info("Daemon shutdown");
  $dbShutdown= new Database();
  $dbShutdown->query("DELETE FROM heartbeat WHERE device=\"poller\" AND component=\"iteration_$iterationCycle\" ");
  $dbShutdown->execute(); 
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}
?>
