<?php
declare(strict_types=1);

// Not a smart solution, however snmp failures err to stdout even though we are dealing with
// them internally
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
// https://alexwebdevelop.com/php-daemons/

// unique pidfile based on iteration cycle so we can kill easier
$pid=getmypid();
$logSeverity=0;

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
$logger = new Logger(basename(__FILE__), $logSeverity, $iterationCycle);

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

function convert($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function sendAlarm ( ?string $alarmEventSummary = "Oops.  Someone forgot to set the event summary for information", ?int $alarmEventSeverity = 2, ?string $alarmEventName = "smartSnmpPoller" ) {
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

function sendHostAlarm ( $alarmEventSummary, $alarmEventSeverity , $alarmEventName , $hostname, ?int $ageOut = 600 ) {
  $alarm2 = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  $alarmInfo2=array( "device" => "undefined", "eventSummary" => "" , "eventName" => "", "eventSeverity" => 0 );

  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo2['device'] = $hostname;
  $alarmInfo2['eventSummary']=$alarmEventSummary;
  $alarmInfo2["eventSeverity"]=$alarmEventSeverity;
  $alarmInfo2["eventName"]=$alarmEventName;
  $alarmInfo2["eventAgeOut"]=$ageOut;

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
  $pid2 = file_get_contents($pidFileName);
  if ( $daemonState == "stop" ) {
    echo "Stopping daemon " . basename(__FILE__) . " pid " . $pid2 . "\n";
    $dbKill = new Database();
    $dbKill->query("DELETE FROM heartbeat WHERE device=\"poller\" AND component=\"iteration_$iterationCycle\" ");
    $dbKill->execute();
    exec ("kill -15 $pid2 &>/dev/null");
    die();
  }
  else {
    die("Daemon already running for " . basename(__FILE__) . " pid: " . $pid2 . "\n");
  }
}
elseif ( $daemonState == "stop" ) {
  ftruncate($pidFile, 0);
  $logger->warning("Daemon stop was called for ". basename(__FILE__) . " but there is no recorded daemon running.  Check for orphans");
  die("Daemon does not have a recorded pid running for " . basename(__FILE__) . "\n");
}
else {
  $pid2 = file_get_contents($pidFileName);
  if (empty($pid2)) {
    $logger->warning("Daemon start was called for ". basename(__FILE__));
  }
  else {
    $logger->warning("Daemon start was called but daemon is already running! This pid is " . $pid . " and within lock file the value is " . $pid2 . ' for '  . basename(__FILE__) );
  }
}
echo "Starting daemon " . basename(__FILE__) . " pid " . $pid . "\n";
// Log our running pid value now
ftruncate($pidFile, 0);
fwrite($pidFile, "$pid");

// This is our daemon loop
while (true) {
  $startSize=convert(memory_get_usage(true));
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      sendAlarm("Daemon has lost its database connection", 5, "smartSnmpPoller-database-" . $iterationCycle);
      unset($db);
      $logger->error("Database failure $this->error");
      sleep(20);
      $db = new Database();
    }
    sendAlarm("Daemon has restored its database connection", 0, "smartSnmpPoller-database-" . $iterationCycle);
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $utcDate=gmdate("Y-m-d H:i:s");
  $db->query("INSERT INTO heartbeat VALUES(\"smartSnmpPoller\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$pid\" ");
  $db->execute();
  $logger->debug("Heartbeat sent");

  /*
    Pull ALL active events so we do not hammer the database with "ok" messages
  */
  $db->query("SELECT device, eventName FROM event");
  $db->execute();
  $activeEvents = $db->resultset();
  $activeEvents = json_decode(json_encode($activeEvents),true);
  // print_r($activeEvents);

  /*
    Pull in oids that match our iteration cycle (limiter) and lastUpdate is older than (now - $iterationCycle)
    Also pulling type of snmp get as well as where we store it at
  */
  $db->query("SELECT * FROM monitoringPoller WHERE iteration=$iterationCycle AND (type='get' OR type='walk') AND (hostname !='' OR hostGroup !='')");
  $db->execute();
  $snmpOidCount=$db->rowCount();
  $snmpOids=$db->resultset();
  $logger->info("smartSnmpPoller table query for oids to poll returned $snmpOidCount rows");
  $snmpChecks=json_decode(json_encode($snmpOids),true);

  // We are goingto count how many hosts per iteration service checks were run against
  $snmpHostLoopCount=0;

  // Figure out if we have hostGroups and append the hosts matching with the hosts that may be defined in hostname
  foreach ($snmpChecks as $keyIndex => $valueCheck) {
    // echo "snmpOids KEY " . $keyIndex . " VALUE ";
    // echo "" .  print_r($valueCheck) . "\n";
    if ( ! empty($valueCheck['hostGroup'])) {
      // We know we have hostgroups to find hostnames for...
      // echo "HOSTGROUP " . $valueCheck['hostGroup'] . "\n";
      // explode the csv and then put dblquotes in place and rebuild from array back to string with commas AND quotes
      // Use this chance to ALSO filter out duplicates!
      unset($singleHostname);
      $convertToCleanCsv=explode(',' , $valueCheck['hostGroup']);
      foreach ($convertToCleanCsv as $eachCleanToCsv) {
        if (empty($singleHostname)) {
          // if someone manually adds quotes, strip the damn things
          $eachCleanToCsv=str_replace('"', '', $eachCleanToCsv);
          $singleHostname ='"' . trim($eachCleanToCsv) . '"';
        }
        else {
          // if someone manually adds quotes, strip the damn things
          $eachCleanToCsv=str_replace('"', '', $eachCleanToCsv);
          $singleHostname .=',"' . trim($eachCleanToCsv) . '"';
        }
      }
      unset($snmpHosts);
      unset($cleanHostnameList);
      $valueCheck['hostGroup'] = $singleHostname;
      $db->query("SELECT group_concat(distinct hostname SEPARATOR ',') as hostname FROM hostGroup WHERE hostgroupName IN (" . $valueCheck['hostGroup'] . ")");
      $db->execute();
      $snmpHosts=$db->resultset();
      $snmpHosts=json_decode(json_encode($snmpHosts),true);
      // print_r($snmpHosts);
      // echo $snmpHosts[0]['hostname'] ;
      // Add or append values into hostname
      if (empty($snmpChecks["$keyIndex"]['hostname'])) {
        $snmpChecks["$keyIndex"]['hostname']= $snmpHosts[0]['hostname'];
      }
      else {
        $snmpChecks["$keyIndex"]['hostname'] .= ', ' . $snmpHosts[0]['hostname'];
      }
      $cleanHostnameList=explode(',', $snmpChecks["$keyIndex"]['hostname']) ;
      $cleanHostnameList=array_map('trim', $cleanHostnameList);
      $cleanHostnameList=array_unique($cleanHostnameList);
      $snmpChecks["$keyIndex"]['hostname'] = implode(',' , $cleanHostnameList);
    }
  }
  // echo "DEBUG :" . print_r($snmpChecks) . "\n";
  // Create snmpChecks[?][hostlist] with all values specific to host
  foreach ($snmpChecks as $key => $appendDetails) {
    // echo "snmpChecks KEY " . $key . " VALUE ";
    $hostListings = explode(',', $appendDetails['hostname']);
    unset ($hostDetail);
    foreach($hostListings as $FQDN) {
      // NOW we get IP address as well as ALL hostAttributes to use if needed.
      $db->query("SELECT h.hostname, h.address, ha.component, ha.name, ha.value, h.monitor FROM host h LEFT OUTER JOIN hostAttribute ha ON h.hostname = ha.hostname WHERE h.monitor=0 AND h.hostname=\"$FQDN\" ");
      $db->execute();
      $hostDetail=$db->resultset();
      $hostDetail=json_decode(json_encode($hostDetail), true);
      //$logger->warning("TEST " . $FQDN . " " . json_encode($hostDetail) . "TEST");
      if (count($hostDetail) == 0) {
        $logger->warning("Query for " . $FQDN . " did not return any hostAttribute values or IP address but it is defined for monitoring in either a hostgroup or specfic host check for this poller.  Please enable monitoring in host talbe to activate monitoring");
        //sendHostAlarm("Host " . $FQDN . " is configured to be montitored, but does not return SNMP settings or is missing its IP address.", 1, "hostAttributes", $FQDN, $iterationCycle); // If we are not monitoring this host, do not alarm on it!  Duh!
      }
      else {
        foreach ($hostDetail as $hostDetails) {
          $snmpChecks["$key"]['hostlist'][$hostDetails['hostname']][$hostDetails['component']][$hostDetails['name']] = $hostDetails['value'];
        }
      }
      if (! count($hostDetail) == 0) {
        $snmpChecks["$key"]['hostlist'][$hostDetails['hostname']]['address'] = $hostDetails['address'];
      }
    }
  }

  //print_r($snmpChecks);

  /*
    [2] => Array
            [id] => 18
            [checkName] => hostOs
            [checkAction] => 1.3.6.1.2.1.1.1.0
            [type] => get
            [iteration] => 300
            [storage] => database
            [hostname] => nas01.iwillfearnoevil.com
            [hostGroup] =>
            [hostlist] => Array
                    [nas01.iwillfearnoevil.com] => Array
                            [SNMP] => Array
                                    [snmpSetup] => public, v2c, 161
                            [address] => 192.168.15.125
  */

  // This is where we should fork our processes to thread stuff out to scale FUTURE!!!

  // I hate that objects cannot be foreach'ed
  foreach($snmpChecks as $nonObjSnmpChecks) {
    // Retrieve each check
    foreach( $nonObjSnmpChecks as $k => $v ) {
      // echo "KEY " . $k . " VALUE " . $v . "\n";
      if ($k == 'checkName') {
        $checkName = "$v"; // alarm name
      }
      elseif ( $k == 'checkAction') {
        $checkCommand2 = "$v"; // OID
      }
      elseif ( $k == 'storage') {
        $snmpStorage = "$v";
      }
      elseif ( $k == 'type') {
        $snmpType = "$v";
      }
    }
    // echo "CHECK NAME " . $checkName . " CHECK ACTION " . $checkCommand2 . "\n";

    // This is what is going to be iterated over since it will be by hostlist
    // We are building out variables that can be called in the checkName
    // print_r($nonObjSnmpChecks);
    foreach ($nonObjSnmpChecks['hostlist'] as $hostKey => $hostValue ) {
      unset ($cmdAttrib['SNMP']['snmpSetup']);
      unset ($cmdAttrib);
      //echo "DEBUG UNSET " . print_r($cmdAttrib) . "\n";
      // print_r($hostnames);
      //echo "DEBUG hostkey => hostValue " . $hostKey . " => " . print_r($hostValue) . "\n";
      $hostname = $hostKey;
      $address  = $hostValue['address'];
      // echo "HOSTNAME " . $hostname . " ADDRESS " . $address . "\n";
      foreach ($hostValue as $attribKey => $attribValue ) {
        // echo "ATTRIB KEY ". $attribKey . " ATTRIB VALUE " . "\n";
        if ( $attribKey !== "address" ) {
          foreach ($attribValue as $attribName => $attribFinal) {
            // echo "SPECIFIC KEY " . $attribName . " SPECIFIC VALUE " . $attribFinal . "\n";
            $cmdAttrib[$attribKey][$attribName] = $attribFinal;
          }
        }
        // print_r($cmdAttrib);
        // At this point we have FQDN, IP, command attibute values!  (yay!)
      }
      unset ($snmpCommunity);
      unset ($snmpVersion);
      unset ($snmpPort);
      if (empty ($cmdAttrib['SNMP']['snmpSetup']) ) {
        $cmdAttrib['SNMP']['snmpSetup'] = '';
      }
      //echo "DEBUG hostlist LOOP " . $hostname . " SNMP VALUES " .  $cmdAttrib['SNMP']['snmpSetup'] . "\n";
      $snmpSetup     = explode(',', $cmdAttrib['SNMP']['snmpSetup']);
      $snmpCommunity = $snmpSetup[0];
      $snmpVer       = $snmpSetup[1];
      $snmpPort      = $snmpSetup[2];
      $snmpOidValue  = $checkCommand2;
      $snmpHostname  = $hostname;
      $snmpAddress   = $address;
      // echo "TESTING OID NUMBER " . $snmpOidValue . "\n";
      // echo "TESTING ATTRIBUTES STRING SNMPSETUP " . $cmdAttrib['SNMP']['snmpSetup'] . "\n";
      // echo "DEBUG: checkName: " . $checkName . " checkCommand: " . $checkCommand2 . " hostname: " . $hostname . " IP " . $address . "\n";
      // echo "DEBUG Community: " . $snmpCommunity . " version " . $snmpVer . " port " . $snmpPort . "\n";

      // We will change this inside the loop to NOT try to send metrics on failures
      $successfulCheck="true";

      $snmpVer=preg_replace('/\D/', '', $snmpVer);
      $snmpVersion=intval($snmpVer);
      // echo "Checking parsed SNMP Version " . $snmpVer . " mapped to \"" . $snmpVersion . "\"\n";
      $logger->debug("Checking parsed SNMP Version " . $snmpVer . " mapped to " . $snmpVersion);

      // Now build out our queries here!
      $logger->debug("SNMP Version " . $snmpVersion . " SNMP ADDRESS " . $snmpAddress . " SNMP COMMUNITY STRING " . $snmpCommunity);
      if ( $snmpVersion != 2) {
        $snmpHostLoopCount++ ;
        $session = new SNMP($snmpVersion, "$snmpAddress", "$snmpCommunity");
      }
      else {
        $snmpHostLoopCount++ ;
        $session = new SNMP(SNMP::VERSION_2c, "$snmpAddress", "$snmpCommunity");
      }
      echo "DEBUG " . $snmpHostname . " VERSION " . $snmpVersion . " COMMUNITY " . $snmpCommunity . " PORT " . $snmpPort . "\n";
      if ( ! empty($snmpVer) && ! empty($snmpCommunity) && ! empty($snmpPort) ) {
        foreach($activeEvents as $validEvent) {
          if ( $validEvent['device'] == $snmpHostname && $validEvent['eventName'] == "hostAttributes" ) {
            $logger->info("Recovery: host " . $snmpHostname . "  now has SNMP values set");
            sendHostAlarm("Host " . $snmpHostname . " now has SNMP hostAttributes set for community, version, and port.", 0, "hostAttributes", $snmpHostname, 601);
          }
        }
      }
      if ( empty($snmpVer) || empty($snmpCommunity) || empty($snmpPort) ) {
        $successfulCheck="false";
        $logger->error("Host " . $snmpHostname . " does not have SNMP hostAttributes set for community, version, or port.");
        sendHostAlarm("Host " . $snmpHostname . " does not have SNMP hostAttributes set for community, version, or port.", 3, "hostAttributes", $snmpHostname, 601);
      }
      elseif ( $snmpType == "get" ){
        $session->enum_print =0 ;
        $session->valueretrieval = SNMP_VALUE_PLAIN;
        $session->oid_output_format = SNMP_OID_OUTPUT_SUFFIX;
        $sessionResult = $session->get("$snmpOidValue");
        if ( $sessionResult == "false" ) {
          $logger->error("SNMP $snmpType failed for $snmpHostname using oid $snmpOidValue");
          sendHostAlarm("SNMP " . $snmpType . " failed using oid " . $snmpOidValue, 3, $checkName, $snmpHostname);
          $successfulCheck="false";
        }
        else {
          $logger->info("SNMP $snmpType success for $snmpHostname for oid $snmpOidValue");
          foreach($activeEvents as $validEvent) {
            if ( $validEvent['device'] == $snmpHostname && $validEvent['eventName'] == $checkName ) {
              sendHostAlarm("SNMP " . $snmpType . " success using oid " . $snmpOidValue, 0, $checkName, $snmpHostname);
              $logger->info("Sending Clear Message: checkName: " . $checkName . " hostname: " . $snmpHostname . " IP " . $snmpAddress);
            }
          }
        }
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
        if ( $sessionResult == "false" ) {
          $logger->error("SNMP $snmpType failed for $snmpHostname using oid $snmpOidValue");
          sendHostAlarm("SNMP " . $snmpType . " failed using oid " . $snmpOidValue, 3, $checkName, $snmpHostname);
          $successfulCheck="false";
        }
        else {
          $logger->info("SNMP $snmpType success for $snmpHostname for oid $snmpOidValue");
          foreach($activeEvents as $validEvent) {
            if ( $validEvent['device'] == $snmpHostname && $validEvent['eventName'] == $checkName ) {
              sendHostAlarm("SNMP " . $snmpType . " success using oid " . $snmpOidValue, 0, $checkName, $snmpHostname);
              $logger->info("Sending Clear Message: checkName: " . $checkName . " hostname: " . $snmpHostname . " IP " . $snmpAddress);
            }
          }
        }
      }
      else {
        $logger->warning("SNMP Type has not been set correctly.  PHP only supports get or walk values with the built in SNMP class");
      }
      $logger->info("Value retrieved for " . $snmpType . ' ' . $snmpAddress . ' ' . $snmpOidValue);
      // $logger->debug("Value retrieved for " . $snmpType . ' ' . $snmpAddress . ' ' . $snmpOidValue .' ' . $sessionResult);
      // print_r($session);
      // echo "Session Error Numeric " . $session->getErrno() . "\n";
      // echo "Session Error String " . $session->getError() . "\n";
      // echo "SessionResult json_encoded " . $sessionResult3 . "\n";

      // Single gets (USUALLY) strings should go in the database
      $logger->info("Storing metrics in " .$snmpStorage . " for " . $snmpHostname . " using oid " . $snmpOidValue);
      if (!empty($sessionResult) && $successfulCheck == "true") {
        if ( $snmpStorage == "database" ) {
          $db->query("INSERT INTO performance VALUES(\"$snmpHostname\", \"$snmpOidValue\", now(), :sessionResult) ON DUPLICATE KEY UPDATE date=NOW(), value= :sessionResult ");
          $pollerUpdate=$db->bind('sessionResult', "$sessionResult");
          $pollerUpdate=$db->execute();
          $logger->info("Insert raw return into database unformatted");
        }
        elseif ($snmpStorage == "databaseMetric") {
          if (file_exists(__DIR__ . "/../../templates/sendMetricToDatabase.php")) {
            require_once __DIR__ . "/../../templates/sendMetricToDatabase.php";
            $sendMetricDatabase=sendMetricToDatabase( $snmpHostname, $sessionResult, $snmpOidValue);
             $logger->debug("Session result data " . $sessionResult);
            if ($sendMetricDatabase == 0) {
              $logger->info("Performance database push complete for " . $snmpHostname . " updating oid " . $snmpOidValue);
              foreach($activeEvents as $validEvent) {
                if ( $validEvent['eventName'] == "smartSnmpPoller-queryPush-" . $iterationCycle ) {
                  sendAlarm("Performance database push success", 0, "smartSnmpPoller-queryPush-" . $iterationCycle);
                  $logger->info("Sending Clear Message: Performance database push success for smartSnmpPoller-queryPush-" . $iterationCycle);
                }
              }
            }
            else {
              $logger->error("Performance database push failed for " . $snmpHostname . " updating oid " . $snmpOidValue);
              sendAlarm("Performance database push failed", 2, "smartSnmpPoller-queryPush-" . $iterationCycle);
            }
          }
          else {
            $logger->error( "Unable to find root template file /templates/sendMetricToDatabase.php");
            sendAlarm("Unable to find template file sendMetricToDatabase.php", 2, "smartSnmpPoller-queryPush-" . $iterationCycle);
          }
        }
        elseif ( $snmpStorage == "graphite" ) {
          $logger->debug("Requested graphite storage metric for " . $snmpHostname . " result ".  $sessionResult . " Oid Value " .  $snmpOidValue);
          if (file_exists(__DIR__ . "/../../templates/sendMetricToGraphite.php")) {
            require_once __DIR__ . "/../../templates/sendMetricToGraphite.php";
            $sendMetricGraphite=sendMetricToGraphite( $snmpHostname, $sessionResult, $snmpOidValue);
            if ($sendMetricGraphite == 0) {
              $logger->info("Metric push to Graphite complete for " . $snmpHostname . " updating oid " . $snmpOidValue);
              foreach($activeEvents as $validEvent) {
                if ( $validEvent['eventName'] == "smartSnmpPoller-queryPush-" . $iterationCycle ) {
                  sendAlarm("Graphite push success", 0, "smartSnmpPoller-queryPush-" . $iterationCycle);
                  $logger->info("Sending Clear Message: Graphite push success for smartSnmpPoller-queryPush-" . $iterationCycle);
                }
              }
            }
            else {
              $logger->error("Metric push to Graphite failed for " . $snmpHostname . " updating oid " . $snmpOidValue);
              sendAlarm("Metric push to Graphite failed", 2, "smartSnmpPoller-queryPush-" . $iterationCycle);
            }
          }
          else {
            $logger->error( "Unable to find root template file /templates/sendMetricToGraphite.php");
            sendAlarm("Unable to find template file sendMetricToGraphite.php", 2, "smartSnmpPoller-queryPush-" . $iterationCycle);
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
        // If we get to this point and the success bit is not flipped, that is a script logic error!
        if ( $successfulCheck == "true" ) {
          sendHostAlarm("Unexpected script error found failure " . $session->getError() . " for " . $snmpHostname . " using oid " . $snmpOidValue, 4, $snmpOidValue , $snmpHostname);
          $logger->error("snmp query returned error for " . $snmpHostname . " message " . $session->getError());
        }
      }
    }
  }


  $endSize = convert(memory_get_usage(true));
  $logger->info("Daemon memory Stats: Beginning of active loop is " . $startSize . " end of loop size is " . $endSize);
  // we have completed our iteration for this cycle, now update info
  // about the daemon itself
  $timeNow=time();
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    sendAlarm("Daemon poller had an iteration overrun.  Confirm daemon is not overloaded in logs delta is " . $timeDelta . " seconds" , 2, "smartSnmpPoller-iterationComplete-" . $iterationCycle);
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined for $snmpOidCount different checks with $snmpHostLoopCount hosts");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
    foreach($activeEvents as $validEvent) {
      if ( $validEvent['eventName'] == "smartSnmpPoller-iterationComplete-" . $iterationCycle ) {
        sendAlarm("Daemon poller iteration complete.  Daemon is not overloaded in logs delta is " . $timeDelta . " seconds", 0, "smartSnmpPoller-iterationComplete-" . $iterationCycle);
        $logger->debug("Sent trap for iteration overrun alarm");
      }
    }
    $logger->info("Iteration complete.  Cycle took $timeDelta seconds to complete for $snmpOidCount different checks with $snmpHostLoopCount hosts");
    $newIterationCycle=( $iterationCycle - $timeDelta );
  }
  sleep($newIterationCycle);
  $sleepDate=time();
}

// This will quit the daemon when a signal is received
function signalHandler($signal) {
  global $iterationCycle;
  global $logSeverity;
  $logger2 = new Logger(basename(__FILE__), $logSeverity, $iterationCycle);
  $logger2->info("Daemon shutdown");
  $dbShutdown= new Database();
  $dbShutdown->query("DELETE FROM heartbeat WHERE device=\"smartSnmpPoller\" AND component=\"iteration_$iterationCycle\" ");
  $dbShutdown->execute(); 
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}
?>
