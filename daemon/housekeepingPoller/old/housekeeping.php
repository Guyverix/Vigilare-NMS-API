<?php
declare(strict_types=1);

$applicationDaemon="housekeeping";

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
  $iterationCycle=300;
}
if (empty($daemonState)) {
  $daemonState='start';
}

// Enable logging system (filename, and minimum sev to log, iterationCycle)
require __DIR__ . '/../../app/Logger.php';
$logger = new Logger(basename(__FILE__), 0, $iterationCycle);

// Enable Metrics logging in Graphite
require __DIR__ . '/../../app/Graphite.php';
$metrics = new Graphite( "nms" , "true");

// Enable Eventing support for daemon
require __DIR__ . '/../../app/Curl.php';

// Start the guts of the daemon here
$sleepDate=time();
date_default_timezone_set('UTC');
$logger->info("Daemon called for iteration cycle of $iterationCycle under pid: $pid to $daemonState daemon");

// Get a database object built
require __DIR__ . '/../../app/Database.php';
$db = new Database();

function sendAlarm ( ?string $alarmEventSummary = "Oops.  Someone forgot to set the event summary for information", ?int $alarmEventSeverity = 2, ?string $alarmEventName = "housekeeping" ) {
  $alarm = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  $alarmInfo=array( "device" => "larvel01.iwillfearnoevil.com", "eventSummary" => "" , "eventName" => "", "eventSeverity" => 0 );
  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo['eventSummary']=$alarmEventSummary;
  $alarmInfo["eventSeverity"]=$alarmEventSeverity;
  $alarmInfo["eventName"]=$alarmEventName;
  $alarmInfo["eventAgeOut"]=86400;
  // Set our details here
  $alarm->data($alarmInfo);
  $alarm->send();
  $alarm->close();
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
    $dbKill->query("DELETE FROM heartbeat WHERE device=\"$applicationDaemon\" AND component=\"iteration_$iterationCycle\" ");
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
  $logger->debug("Daemon start was called and is now running " . basename(__FILE__) );
}

// housekeeping should watchdog that snmptrapd is running as well
function isProcessRunning($pidFile = '/run/snmptrapd.pid') {
  if (!file_exists($pidFile) || !is_file($pidFile)) return false;
  $pid = intval(file_get_contents($pidFile));
  // return posix_kill($pid, 0);
  return $pid;
}




echo "Starting daemon " . basename(__FILE__) . " pid " . $pid . "\n";
// Log our running pid value now
ftruncate($pidFile, 0);
fwrite($pidFile, "$pid");

// Daemon loop starts now
while (true) {
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      sendAlarm("Daemon has lost its database connection", 5, $applicationDaemon . "-database-" . $iterationCycle);
      unset($db);
      $logger->error("Database failure $db->error");
      sleep(20);
      $db = new Database();
    }
    sendAlarm("Daemon has restored its database connection", 0, $applicationDaemon . "-database-" . $iterationCycle);
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $utcDate=gmdate("Y-m-d H:i:s");
  $db->query("INSERT INTO heartbeat VALUES(\"$applicationDaemon\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$pid\" ");
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


  // Each iteration we need to make sure the database is clean inside the event table.
  // We are going to age out events that have not been updated within the ageOut value

  $db->query("SELECT evid FROM event WHERE stateChange <= NOW() - INTERVAL eventAgeOut SECOND");
  $listEvid=$db->resultset();
  $housekeepingCount=$db->rowCount();
  $logger->info("found $housekeepingCount entries to move to history");

  // If we have rows to move
  if ($housekeepingCount > 0) {

    // Create a comma string list from the object returned
    $evid=implode('","', array_map(function($c) {
      return $c->evid;
    },  $listEvid));
    // Add beginning and end quotes
    $evid='"' . $evid . '"';

    //echo "INSERT INTO history SELECT e.* FROM event e WHERE e.evid IN( $evid )";
    //echo "DELETE FROM history  WHERE evid IN( $evid )";

    $db->query("INSERT INTO history SELECT e.* FROM event e WHERE e.evid IN( $evid )");
    $insertDb=$db->execute();
    if ( ! empty($db->error) ) {
      $logger->error("Database error $db->error");
    }
    $logger->info("Moved $housekeepingCount entries to history");

    $db->query("UPDATE history SET endEvent=now() WHERE evid IN( $evid )");
    $updateDb=$db->execute();
    $logger->info("Updated $housekeepingCount entries to endEvent time ");
    if ( ! empty($db->error) ) {
      $logger->error("Database error $db->error");
    }

    $db->query("UPDATE history SET eventDetails=CONCAT(eventDetails,',[ automated age out ]') WHERE evid IN( $evid )");
    $updateDb=$db->execute();
    $logger->info("Updated $housekeepingCount entries to reflect this is an automated age out ");
    if ( ! empty($db->error) ) {
      $logger->error("Database error $db->error");
    }

    $db->query("DELETE FROM event WHERE evid IN( $evid )");
    $deleteDb=$db->execute();
    $logger->info("Deleted $housekeepingCount entries from event");
    if ( ! empty($db->error) ) {
      $logger->error("Database error $db->error");
    }
  }

  $trapd=isProcessRunning();
  if ( ! empty($trapd)) {
   $utcDate=gmdate("Y-m-d H:i:s");
   $db->query("INSERT INTO heartbeat VALUES(\"snmptrapd\", \"iteration_60\",now(), \"$trapd\") ON DUPLICATE KEY UPDATE  lastTime=\"$utcDate\" , pid=\"$trapd\" ");
   $db->execute();
   $logger->debug("Heartbeat set for snmptrapd");
   $almSev=0;
   $almSum="Found snmptrapd daemon running.";
   $almNam="snmptrapd";
   sendAlarm( $almSum , $almSev , $almNam );
  }
  else {
   $almSum="daemon snmptrapd is not running.";
   $almSev=5;
   $almNam="snmptrapd";
   sendAlarm( $almSum , $almSev , $almNam );
   $logger->error("Failed to set heartbeat for snmptrapd");
  }

  // Next job is to alarm if hearbeats are not being updated
  $db->query("SELECT device, component FROM heartbeat");
  $heartbeatList=$db->resultset();
  $heartbeatTimes=array();

  foreach ($heartbeatList as $hbList) {
    foreach ($hbList as $k => $v) {
      $hbTimer='';
      $hbDevice=$k;
      if ($k == "component") {
        //      $v = strstr($v, '_'
        //      $v = ltrim(strstr("$v", '_'), '_');
        $v = strstr("$v", '_');
        $hbTimer = ltrim("$v", '_');
        $result[0]["component"] = "$hbTimer";
      }
      else  {
        $hbTimer = $v;
        $result[0]["device"] = "$hbTimer";
      }
    }
    $heartbeatTimes[] = $result[0];
  }
  //  print_r( $heartbeatTimes );
  //  print_r( $heartbeatList );


  foreach ($heartbeatTimes as $hbCheck) {
    $hbIntTimer=( $hbCheck['component'] * 2 );
    $db->query("SELECT device, lastTime, pid FROM heartbeat WHERE device= :device AND component LIKE :rawTimer AND lastTime <= (NOW() - INTERVAL :hbTimer SECOND)");
    $db->bind('device', $hbCheck['device']);
    $db->bind('hbTimer', $hbIntTimer);
    $db->bind('rawTimer', "%" . $hbCheck['component']);
    $db->execute();
    if ( $db->rowcount() > 0 ) {
      $almSum="No fresh heartbeat for daemon " . $hbCheck['device'] . " with an iteration cycle of " .  $hbCheck['component'] . ".";
      $almSev=5;
      $almNam="heartbeat-" . $hbCheck['device'] . "-" . $hbCheck['component'];
      sendAlarm( $almSum , $almSev , $almNam );
      $logger->critical("heartbeat for " . $hbCheck['device'] . " Iteration cycle " . $hbCheck['component'] . " is not healthy");
    }
    else {
      foreach($activeEvents as $validEvent) {
        if ( $validEvent['eventName'] == "heartbeat-" . $hbCheck['device'] . "-" . $hbCheck['component'] ) {
          sendAlarm( "heartbeat for " . $hbCheck['device'] . " Iteration cycle " . $hbCheck['component'] . " is healthy", 0 , "heartbeat-" . $hbCheck['device'] . "-" . $hbCheck['component']);
        }
      }
      $logger->debug("heartbeat for " . $hbCheck['device'] . " Iteration cycle " . $hbCheck['component'] . " is healthy");
    }
  }

  // Next job is to remove alarm suppression from the event.maintenance page
  $db->query("SELECT * FROM maintenance WHERE end_time <= NOW()");
  $db->execute();
  $maintCount=$db->rowCount();
    $logger->debug("housekeeping preparing to delete $maintCount expired maintenance windows");
  if ($maintCount > 0) {
    $logger->debug("housekeeping preparing to delete $maintCount expired maintenance windows");
    $db->query("DELETE FROM maintenance WHERE end_time <= NOW()");
    $db->execute();
  }
  $logger->info("housekeeping deleted $maintCount expired maintenance windows");

  // we have completed our iteration for this cycle, now udpate info
  // about the daemon itself
  $timeNow=time();
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    sendAlarm("Daemon poller had an iteration overrun.  Confirm daemon is not overloaded in logs delta is " . $timeDelta . " seconds", 2, "housekeeping-iterationComplete-" . $iterationCycle);
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
    foreach($activeEvents as $validEvent) {
      if ( $validEvent['eventName'] == "housekeeping-iterationComplete-" . $iterationCycle) {
        sendAlarm("Daemon poller iteration complete.  Daemon is not overloaded in logs delta is " . $timeDelta . " seconds", 0, "housekeeping-iterationComplete-" . $iterationCycle);
      }
    }
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
  $dbShutdown->query("DELETE FROM heartbeat WHERE device=\"$applicationDaemon\" AND component=\"iteration_$iterationCycle\" ");
  $dbShutdown->execute(); 
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}
?>

