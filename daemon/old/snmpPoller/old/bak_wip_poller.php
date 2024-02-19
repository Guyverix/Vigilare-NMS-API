<?php
declare(strict_types=1);

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
require __DIR__ . '/../app/Logger.php';
$logger = new Logger(basename(__FILE__), 0, $iterationCycle);

// Enable Metrics logging in Graphite
require __DIR__ . '/../app/Graphite.php';
$metrics = new Graphite( "nms" , "true");

// Start the guts of the daemon here
$sleepDate=time();
date_default_timezone_set('UTC');
$logger->info("Daemon start with iteration cycle of $iterationCycle under pid: $pid");

// Get a database object built
require __DIR__ . '/../app/Database.php';
$db = new Database();

/*
// Debugging database object
var_dump($db);
print_r($db);
echo $db->error;
*/

// This will allow different daemons with different
// iteration cycles to run side by side
$pidFileName = basename(__FILE__) . '.' . $iterationCycle . '.pid';
$pidFile = @fopen($pidFileName, 'c');
if (!$pidFile) die("Could not open $pidFileName\n");
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
  die("Daemon does not have a recorded pid running for " . basename(__FILE__) . "\n");
}
else {
  echo "Starting daemon " . basename(__FILE__) . " pid " . $pid . "\n";
  // Log our running pid value now
  ftruncate($pidFile, 0);
  fwrite($pidFile, "$pid");
}

/*
This is what we need to get from the database.
$arr=["1.3.6.1.2.1.1.6.0", "public" , "2c", "192.168.15.58"];
*/


// This is our daemon loop
while (true) {
  // heal from db connection problems
  if ( ! empty($db->error) || ! isset($db)) {
    while ( ! empty($db->error) || ! isset($db)) {
      unset($db);
      $logger->error("Database failure $this->error");
      sleep(20);
      $db = new Database();
    }
    $logger->info("Database reconnected");
  }
  // Update heartbeat each iteration
  $db->query("INSERT INTO heartbeat VALUES(\"poller\", \"iteration_$iterationCycle\",now(), \"$pid\") ON DUPLICATE KEY UPDATE  lastTime=now(), pid=\"$pid\" ");
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
      // echo "\$snmpOid[$oid] => $id\n";  // DEBUG LOOP
      if ( $oid == 'oid' ) {
        $oidValue = $id ;
      }
      if ( $oid == 'hostname' ) {
        $db->query("SELECT h.hostname, h.address, a.value AS snmpSetup FROM host h INNER JOIN hostAttribute a ON h.hostname = a.hostname WHERE h.id=$id AND a.hostname = h.hostname AND a.name = 'snmpSetup'");
        $result= json_decode(json_encode($db->resultset()), true);
        // convert object to array
        // append the oid we are using into this array
        if (! empty($result)) {
          $result[0]["oid"] = "$oidValue" ;
          $snmpHosts[] = $result[0];
        }
      }
      if ( $oid == 'type' ) {
//        $oidValue = $id;
echo "TYPE foreach snmpOid as oid $oid value $id \n";
        $result1["$oid"] = "$id";
//        $snmpHosts[] = array_push($result1[0]);
//        array_push($snmpHosts[], $result1["$oid"]);
        $snmpHosts[] = $result1["$oid"];
      }
/*      if ( $oid == 'storage' ) {
        $result2[0]["storage"] = "$id";
        $snmpHosts[] = $result2[0];
echo "STORAGE foreach snmpOid as oid $oid value $id \n";
      }
*/
  print_r($snmpHosts);
    }
  }
  // $snmpHosts[] contains array of: hostname, address, oid, (string of community, version, port) OID wants to query
  //print_r($result);

exit();

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
    }

    // Set our SNMP version to exactly what is expected
    switch ($snmpVer) {
      case preg_match('/(.*?)1(.*?)/i', $snmpVer):
        $snmpVersion=1;
        break;
      case preg_match('/(.*?)2(.*?)/i', $snmpVer):
        $snmpVersion=2;
        break;
      case preg_match('/(.*?)3(.*?)/i', $snmpVer):
        $snmpVersion=3;
        break;
    }
    // Now build out our query here!
    $session = new SNMP($snmpVersion, "$snmpAddress", "$snmpCommunity");
    $sessionResult = $session->get("$snmpOidValue");
    $logger->debug("Loop retrieved for " . $snmpAddress . ' ' . $sessionResult);


    // Single gets (USUALLY) strings should go in the database
    if (!empty($sessionResult)) {
     // escape out our " characters!
     $sessionResult=str_replace('"','\"', $sessionResult);
     $db->query("INSERT INTO performance VALUES(\"$snmpHostname\", \"$snmpOidValue\",now(), \"$sessionResult\") ON DUPLICATE KEY UPDATE  date=now(), value=\"$sessionResult\" ");
     $pollerUpdate=$db->execute();
    }
  }

  // we have completed our iteration for this cycle, now udpate info
  // about the daemon itself
  $timeNow=time();
  if ( ($sleepDate + $iterationCycle) <= $timeNow ) {
    $timeDelta=( $timeNow - ($sleepDate + $iterationCycle));
    $logger->error("Iteration overrun.  Cycle took $timeDelta seconds beyond the iteration defined");
    $newIterationCycle=1;
  }
  else {
    $timeDelta=($timeNow - $sleepDate);
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
