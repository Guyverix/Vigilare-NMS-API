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

function sendAlarm ( ?string $alarmEventSummary = "Someone forgot to set an alarm summary", ?int $alarmEventSeverity = 1, ?string $alarmEventName = "unknown", ?string $device = "larvel01.iwillfearnoevil.com", ?string $details = "Undefined details" ) {
  $logger3 = new Logger(basename(__FILE__), 0, $iterationCycle);
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
  $pid= file_get_contents($pidFileName);
  if ( $daemonState == "stop" ) {
    echo "Stopping daemon " . basename(__FILE__) . " pid " . $pid . "\n";
    $dbKill = new Database();
    $dbKill->query("DELETE FROM heartbeat WHERE device=\"shellPoller\" AND component=\"iteration_$iterationCycle\" ");
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
$arr=["check-name", "command", "host", "iteration"];
*/


// This is our daemon loop
while (true) {
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      sendAlarm("Daemon has lost its database connection", 5, "shellPoller-database-" . $iterationCycle);
      unset($db);
      $logger->error("Database failure $this->error");
      sleep(20);
      $db = new Database();
    }
    sendAlarm("Daemon has restored its database connection", 0, "shellPoller-database-" . $iterationCycle);
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $utcDate=gmdate("Y-m-d H:i:s");
  $db->query("INSERT INTO heartbeat VALUES(\"shellPoller\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$pid\" ");
  $db->execute();
  $logger->debug("Heartbeat sent");

  /*
    Pull in commands that match our iteration cycle (limiter) and lastUpdate is older than (now - $iterationCycle)
  */
  $db->query("SELECT checkName, checkCommand, host FROM shellPoller WHERE iteration=$iterationCycle");
  $db->execute();
  $shellCheckCount=$db->rowCount();
  $shellChecks=$db->resultset();
  $logger->info("shellPoller table query for monitors to poll returned $shellCheckCount rows");


  //print_r($shellChecks);
  foreach( $shellChecks as $nonObjShellChecks) {
    //print_r($nonObjShellChecks);
    foreach( $nonObjShellChecks as $k => $v ) {
      if ($k == 'checkName') {
      $checkName="$v";
      }
      if ( $k == 'checkCommand') {
      $checkCommand="$v";
      }
      if ( $k == 'host' ) {
        $checkHost="$v";
      }
    }
    // echo "DEBUG: checkName " .$checkName . " checkCommand " . $checkCommand . " hostname " . $checkHost . "\n";
    $result=exec($checkCommand , $output, $result_code);
    if ($result_code == 0 ) {
      // Success
      $result=preg_replace( '/\|.*./', '', $result);
      sendAlarm("$result", 0, $checkName, $checkHost, $completeOutput);
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
          $severity=3;
          break;
        default:
          $severity=2;
          break;
      }
      foreach ($output as $merge) {
        $completeOutput .= " " . $merge;
      }
      sendAlarm("$result", $severity, $checkName, $checkHost, $completeOutput);
    }
    /* attempt to parse any metrics returned */
    $completeOutput='';
    foreach ($output as $merge) {
      $completeOutput .= " " . $merge;
    }
    $rawMetric=explode('|', $completeOutput)[1];
    $rawMetric=preg_replace( '/;;;.*/','',$rawMetric);
    // time=0.026175s;2.000000;3.000000;0.000000;10.000000 size=6099B;;;0
    // echo "RAW: " . $rawMetric . "\n";
    $seporateMetric=explode(' ', $rawMetric);
    //print_r($seporateMetric);

    /* all this crap so we can make sure we have numbers to send to graphite */
    foreach ($seporateMetric as $checkMetric) {
      //      echo "checkMetric " .  $checkMetric . "\n";
      if (strpos($checkMetric, '=') !== false) {
        $metricExplode=explode('=', $checkMetric);
        $metricName=trim($metricExplode[0], ' ');
        $metricValue=trim($metricExplode[1], ' ');
        $metricValue=explode(';',$metricValue)[0];
        $metricValue=preg_replace("/[^0-9.,]/", "", $metricValue);
      }
      // echo "RESULT = " . $metricName . " " . $metricValue . "\n";
      if (is_numeric($metricValue)) {
        /* no periods in hostname! */
        $checkHost=preg_replace('/\./', '_', $checkHost);
        $graphiteKey=$checkHost . ".shellPoller." . $checkName . '.' . $metricName;
        /* At this point we have all data needed to send to Graphite */
        $graphite->testMetric( $graphiteKey, $metricValue);
      }
    }
  }

  // echo "Output "  . print_r($output) . "\n";
  // echo "Response ". $result ."\n";
  // echo "result code: " .$result_code . "\n";

  // we have completed our iteration for this cycle, now udpate info
  // about the daemon itself
  unset($shellChecks);
  $timeNow=time();
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    sendAlarm("Daemon poller had an iteration overrun.  Confirm daemon is not overloaded in logs", 2, "shellPoller-iterationComplete-" . $iterationCycle);
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
    sendAlarm("Daemon poller iteration complete.  Daemon is not overloaded in logs", 0, "shellPoller-iterationComplete-" . $iterationCycle);
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
  $dbShutdown->query("DELETE FROM heartbeat WHERE device=\"shellPoller\" AND component=\"iteration_$iterationCycle\" ");
  $dbShutdown->execute(); 
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}

?>
