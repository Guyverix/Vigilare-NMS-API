<?php
declare(strict_types=1);

// Not a smart solution, however snmp failures err to stdout even though we are dealing with
// them internally
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
// https://alexwebdevelop.com/php-daemons/

// unique pidfile based on iteration cycle so we can kill easier
$pid=getmypid();
$logSeverity=2;

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
$graphite = new Graphite();

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

$shellPath='/usr/lib/nagios/plugins/';

function sendAlarm ( ?string $alarmEventSummary = "Someone forgot to set an alarm summary", ?int $alarmEventSeverity = 1, ?string $alarmEventName = "unknown", ?string $device = "larvel01.iwillfearnoevil.com", ?string $details = "Undefined details" ) {
  global $logSeverity;
  $logger3 = new Logger(basename(__FILE__), $logSeverity, $iterationCycle);
  $alarmInfo=array( "device" => "larvel01.iwillfearnoevil.com", "eventSummary" => "Unset" , "eventName" => "Unset", "eventSeverity" => 1, "eventDetails" => "Undefined details");
  $alarm = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo['eventSummary']=$alarmEventSummary;
  $alarmInfo["eventSeverity"]=$alarmEventSeverity;
  $alarmInfo["eventName"]=$alarmEventName;
  $alarmInfo["device"]=$device;
  if (! empty($details)) { $alarmInfo["eventDetails"]=$details; }
  // Set our details here
  $alarm->data($alarmInfo);
  $alarm->send();
  $alarm->close();
  $sent=json_encode($alarmInfo);
  $logger3->debug("Sent to Trap URL ". $sent);
  // No need to keep this in RAM, as object should be rarely used
  unset ($alarm);
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
    $dbKill->query("DELETE FROM heartbeat WHERE device=\"shellPoller\" AND component=\"iteration_$iterationCycle\" ");
    $dbKill->execute();
    exec ("kill -15 $pid2 &>/dev/null");
    die();
  }
  else {
    $logger->warning("Daemon already running for " . basename(__FILE__) . " under pid: " . $pid2);
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

/*
This is what we need to get from the database.
$arr=["check-name", "command", "host", "iteration"];
*/


// This is our daemon loop
while (true) {
  $startSize=convert(memory_get_usage(true));
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      sendAlarm("Daemon has lost its database connection", 5, "smartShellPoller-database-" . $iterationCycle);
      unset($db);
      $logger->error("Database failure $this->error");
      sleep(20);
      $db = new Database();
    }
    sendAlarm("Daemon has restored its database connection", 0, "smartShellPoller-database-" . $iterationCycle);
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $utcDate=gmdate("Y-m-d H:i:s");
  $db->query("INSERT INTO heartbeat VALUES(\"smartShellPoller\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$pid\" ");
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
    Pull in commands that match our iteration cycle (limiter) and lastUpdate is older than (now - $iterationCycle)
  */
  unset ($shellChecks);
  $db->query("SELECT * FROM monitoringPoller WHERE iteration=$iterationCycle AND type='shell' AND (hostname !='' OR hostGroup !='')");
  $db->execute();
  $shellCheckCount=$db->rowCount();
  $shellChecks=$db->resultset();
  $shellChecks=json_decode(json_encode($shellChecks),true);
  $logger->info("shellPoller table query for monitors to poll returned $shellCheckCount rows");
  $shellHostLoopCount=0;

  // Figure out if we have hostGroups and append the hosts matching with the hosts that may be defined in hostname
  foreach ($shellChecks as $keyIndex => $valueCheck) {
    // echo "KEY " . $keyIndex . " VALUE ";
    // echo "" .  print_r($valueCheck) . "\n";
    if ( ! empty($valueCheck['hostGroup'])) {
      // We know we have hostgroups to find hostnames for...
      // echo "HOSTGROUP " . $valueCheck['hostGroup'] . "\n";
      // explode the csv and then put dblquotes in place and rebuild from array back to string with commas AND quotes
      // Use this chance to ALSO filter out duplicates!
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
      $valueCheck['hostGroup'] = $singleHostname;
      unset ($shellHosts);
      // echo "SELECT group_concat(distinct hostname SEPARATOR ',') as hostname FROM hostGroup WHERE hostgroupName IN (" . $valueCheck['hostGroup'] . ") \n";
      $db->query("SELECT group_concat(distinct hostname SEPARATOR ',') as hostname FROM hostGroup WHERE hostgroupName IN (" . $valueCheck['hostGroup'] . ")");
      $db->execute();
      $shellHosts=$db->resultset();
      $shellHosts=json_decode(json_encode($shellHosts),true);
      // print_r($shellHosts);
      // echo $shellHosts[0]['hostname'] . "\n" ;
      // Add or append values into hostname
      if (empty($shellChecks["$keyIndex"]['hostname'])) {
        $shellChecks["$keyIndex"]['hostname']= $shellHosts[0]['hostname'];
      }
      else {
        $shellChecks["$keyIndex"]['hostname'] .= ', ' . $shellHosts[0]['hostname'];
      }
      $cleanHostnameList=explode(',', $shellChecks["$keyIndex"]['hostname']) ;
      $cleanHostnameList=array_map('trim', $cleanHostnameList);
      $cleanHostnameList=array_unique($cleanHostnameList);
      $shellChecks["$keyIndex"]['hostname'] = implode(',' , $cleanHostnameList);
    }
  }
  // Create shellChecks[?][hostlist] with all values specific to host
  foreach ($shellChecks as $key => $appendDetails) {
    // echo "KEY " . $key . " VALUE ";
    $hostListings = explode(',', $appendDetails['hostname']);
    foreach($hostListings as $FQDN) {
      // NOW we get IP address as well as ALL hostAttributes to use if needed.
      $db->query("SELECT h.hostname, h.address, ha.component, ha.name, ha.value FROM host h LEFT OUTER JOIN hostAttribute ha ON h.hostname = ha.hostname WHERE h.hostname=\"$FQDN\" ");
      $db->execute();
      $hostDetail=$db->resultset();
      $hostDetail=json_decode(json_encode($hostDetail), true);
      foreach ($hostDetail as $hostDetails) {
        $shellChecks["$key"]['hostlist'][$hostDetails['hostname']][$hostDetails['component']][$hostDetails['name']] = $hostDetails['value'];
      }
    $shellChecks["$key"]['hostlist'][$hostDetails['hostname']]['address'] = $hostDetails['address'];
    }
  }

  // This is where we should fork our processes to thread stuff out to scale FUTURE!!!

  // I hate that objects cannot be foreach'ed
  foreach($shellChecks as $nonObjShellChecks) {
    // Retrieve each check
    foreach( $nonObjShellChecks as $k => $v ) {
      // echo "KEY " . $k . " VALUE " . $v . "\n";
      if ($k == 'checkName') {
        $checkName="$v";
      }
      elseif ( $k == 'checkAction') {
        $checkCommand2="$v";
      }
    }
    // echo "CHECK NAME " . $checkName . " CHECK ACTION " . $checkCommand . "\n";

    // This is what is going to be iterated over since it will be by hostlist
    // We are building out variables that can be called in the checkName
    // print_r($nonObjShellChecks);

    foreach ($nonObjShellChecks['hostlist'] as $hostKey => $hostValue ) {
      // print_r($hostnames);
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
        // At this point we have FQDN, IP, command attibute values
        // Now convert any values we have in checkCommand to a string
      }
      // echo "VALS : " . $hostname . "\n";
      //echo "TESTING COMMAND " . $checkCommand2 . "\n";
      // echo "TESTING ATTRIBUTES " . print_r($cmdAttrib) . "\n";
      // echo "TESTING ATTRIBUTES STRING SNMPSETUP" . $cmdAttrib['SNMP']['snmpSetup'] . "\n";
      // echo "TESTING COMMAND : " . $checkCommand2 . "\n";
      $checkCommand = eval( 'return "' . $checkCommand2 . '";') ;
      unset ($completeOutput);
      unset ($output);
      unset ($result_code);
      unset ($rawMetric);
      unset ($seporateMetric);

      $concatName=$shellPath . $checkName;
      // echo "Running shell command: " . $concatName . " values " . $checkCommand . "\n";

      $logger->info("Running shell command: " . $concatName . " " . $checkCommand);
      $shellHostLoopCount++ ;
      $result=exec( "$concatName $checkCommand 2>&1" , $output, $result_code);
      // echo "complete\n";
      if ($result_code == 0 ) {
        // Success
        $alarmCompleteOutput=preg_replace('/\|/',' ', $completeOutput);
        foreach($activeEvents as $validEvent) {
          if ( $validEvent['device'] == $hostname && $validEvent['eventName'] == $checkName ) {
            sendAlarm("$result", 0, $checkName, $hostname, $alarmCompleteOutput);
            $logger->info("Sending Clear Message: checkName: " . $checkName . " hostname: " . $hostname . " IP " . $address);
          }
        }
      }
      else {
        // Failure.  Send an Event.  NO METRICS?
        /* summary , severity, alarmName, hostname, details */
        $completeOutput='';
        switch ($result_code) {
          case "1":
            $severity=4;
            break;
          case "2":
            $severity=5;
            break;
          case "3":
            $severity=5;
            break;
          default:
            $severity=3;
            break;
        }
        foreach ($output as $merge) {
          $completeOutput .= " " . $merge;
        }
        //echo "shell alarm: ". $result . " severity " . " check_name " . $checkName . " checkHost " . $hostname . " completeOutput " . $completeOutput . "\n";
        $logger->error("shell alarm: ". $result . " severity " . " check_name " . $checkName . " checkHost " . $hostname . " completeOutput " . $completeOutput);
        // Database seems to choke on pipes
        $alarmCompleteOutput=preg_replace('/\|/',' ', $completeOutput);
        if (empty($result)) {
          $result="shell command failure. Args Used: " . $checkCommand;
        }
        sendAlarm("$result", $severity, $checkName, $hostname, $alarmCompleteOutput);
      }
      /*
      attempt to parse any metrics returned
      EXAMLE: OK - 73.7% (24163380 kB) free.|TOTAL=32780800KB;;;; USED=8617420KB;29502720;31141760;; FREE=24163380KB;;;; CACHES=11719072KB;;;;
      */
      $completeOutput='';
      foreach ($output as $merge) {
        $completeOutput .= " " . $merge;
      }
      $rawMetric=explode('|', $completeOutput)[1];
      /*
      EXAMPLE OUTPUT
      TOTAL=32780800KB;;;; USED=8617420KB;29502720;31141760;; FREE=24163380KB;;;; CACHES=11719072KB;;;;
      time=0.026175s;2.000000;3.000000;0.000000;10.000000 size=6099B;;;0
      */
      if(empty($rawMetric)) {$rawMetric='';}
      $rawMetric=trim($rawMetric);
      $seporateMetric=explode(' ', "$rawMetric");
      // print_r($seporateMetric);

      $cleanMetrics=array();
      foreach ($seporateMetric as $dirtyMetric) {
        if (strpos($dirtyMetric, '=') !== false) {
          $dirtyMetric=preg_replace( '/;.*./', '', $dirtyMetric);
          $logger->debug("shell " . $checkName . " metric data appears to be legitamite " . $dirtyMetric);
          $cleanMetrics[]=$dirtyMetric;
        }
      }
      foreach ($cleanMetrics as $checkMetric) {
        // echo "checkMetric " .  $checkMetric . "\n";
        if (strpos($checkMetric, '=') !== false) {
          $metricExplode=explode('=', $checkMetric);
          $metricName=trim($metricExplode[0], ' ');
          $metricName=preg_replace('/\//', '_' , $metricName);
          $metricName=preg_replace('/ /', '_' , $metricName);
          $metricName=preg_replace('/"/', '', $metricName);
          $metricName=preg_replace('/\./', '', $metricName);

          $logger->debug("Find metric name from " . $checkMetric . " Post explode " . $metricName . " Array trimmed " . $metricExplode[0]);
          $mName=$metricName;
          $metricValue=trim($metricExplode[1], ' ');
          $metricValue=explode(';',$metricValue)[0];
          $metricValue=preg_replace("/[^0-9.,]/", "", $metricValue);
        }
        if (is_numeric($metricValue)) {
          /* no periods in hostname in graphite! */
          $hostnameGraphite=preg_replace('/\./', '_', $hostname);
          $graphiteKey=$hostnameGraphite . ".shellPoller." . $checkName . '.' . $mName . '';
          /* At this point we have all data needed to send to Graphite */
          $gResult = $graphite->testMetric( $graphiteKey, $metricValue);
          $logger->debug("Graphite metric pushed to host " . $graphiteKey . " with value: " . $metricValue . " Results: " . $gResult . " ");
        }
      }

      // Now that we have pushed metrics, we need to save our state into the database
      // echo "Output "  . print_r($output) . "\n";
      // echo "Response ". $result ."\n";
      // echo "result code: " .$result_code . "\n";

      $perfQuery="INSERT INTO performance (hostname, checkName, date, value) VALUES(\"$hostname\", \"$checkName\", NOW(), \"$completeOutput\") ON DUPLICATE KEY UPDATE date= NOW(), value= \"$completeOutput\"";
      $db->query( $perfQuery );
      $db->execute();
      $shellChecks=$db->resultset();
      $logger->debug("shellPoller stored result output to performace database for " . $hostname . " " . $checkName);
    }
    unset ($checkCommand);  // after running the eval
    // echo "Iteration complete\n";
    // we have completed our iteration for this cycle, now udpate info
    // about the daemon itself
  }
  $timeNow=time();
  $endSize = convert(memory_get_usage(true));
  $endPeak = convert(memory_get_peak_usage(true));
  $logger->info("Daemon memory Stats: Beginning of active loop is " . $startSize . " end of loop size is " . $endSize . " with a peak usage value of " . $endPeak );
  unset($shellChecks);
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    sendAlarm("Daemon poller had an iteration overrun.  Confirm daemon is not overloaded in logs", 2, "smartShellPoller-iterationComplete-" . $iterationCycle);
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
    foreach($activeEvents as $validEvent) {
      if ( $validEvent['eventName'] == "smartShellPoller-iterationComplete-" . $iterationCycle ) {
        sendAlarm("Daemon poller iteration complete.  Daemon is not overloaded in logs delta is " . $timeDelta . " seconds", 0, "smartShellPoller-iterationComplete-" . $iterationCycle);
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
  $dbShutdown->query("DELETE FROM heartbeat WHERE device=\"smartShellPoller\" AND component=\"iteration_$iterationCycle\" ");
  $dbShutdown->execute(); 
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}

?>
