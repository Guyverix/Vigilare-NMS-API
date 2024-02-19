<?php
declare(strict_types=1);

// https://alexwebdevelop.com/php-daemons/

// unique pidfile based on iteration cycle so we can kill easier
$pid=getmypid();
$iterationCycle=3;

// Enable logging system (filename, and minimum sev to log, iterationCycle)
require __DIR__ . '/../app/Logger.php';
$logger = new Logger(basename(__FILE__), 0, $iterationCycle);

// Enable Metrics logging in Graphite
require __DIR__ . '/../app/Graphite.php';
$metrics = new Graphite( "nms" , "true");

// Support daemon shutdown
pcntl_async_signals(true);
pcntl_signal(SIGTERM, 'signalHandler'); // Termination (kill was called)
pcntl_signal(SIGHUP, 'signalHandler');  // Terminal log-out
pcntl_signal(SIGINT, 'signalHandler');  // Interrupted (Ctrl-C is pressed) (when in foreground)


// This will allow different daemons with different
// iteration cycles to run side by side
$pidFileName = basename(__FILE__) . '.' . $iterationCycle . '.pid';
$pidFile = @fopen($pidFileName, 'c');
if (!$pidFile) die("Could not open $pidFileName\n");
if (!@flock($pidFile, LOCK_EX | LOCK_NB)) {
  $pid= file_get_contents($pidFileName);
  die("Already running pid: " . $pid . "\n");
}
// Log our running pid value now
ftruncate($pidFile, 0);
fwrite($pidFile, "$pid");

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
  // Pull in oids that match our iteration cycle (limiter) and lastUpdate is older than (now - $iterationCycle)
  $db->query("SELECT oid, hostname FROM snmpPoller WHERE iteration=$iterationCycle AND lastRun <= NOW() - INTERVAL $iterationCycle SECOND");
  $db->execute();
  $snmpOidCount=$db->rowCount();
  $snmpOids=$db->resultset();
  $logger->info("Database query for oids to poll returned $snmpOidCount rows");

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
    }
  }
// $snmpHosts[] contains array of: hostname, address, oid, (string of community, version, port) OID wants to query

//print_r($result);
//print_r($snmpHosts);
//exit;

  // Update the timestamp for the last full pull of oids
//  $db->query("UPDATE snmpPoller SET lastUpdate=now() WHERE oid=$queryOid AND iteration=$iterationCycle AND hostname=$id");
//  $pollerUpdate=$db->execute();

  // This is where we do our double foreach loops
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
    $logger->debug("Loop retrieved " . $sessionResult);



  }
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


exit ;

/*

  // This will be going to a function call so we can state if we are doing get or walk
  // Bulk will be its own thing as behavior gets goofy.
  $session = new SNMP(SNMP::VERSION_1, "192.168.15.58", "public");
  $sysdescr = $session->get("1.3.6.1.2.1.1.6.0");
  $logger->debug("Retrieved ". $sysdescr);

  // See how long the iteration took and adjust our sleep timer to make sure
  // our cycles do not drift much.  Perfection is not necessary, but being close
  // is important.
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
*/
// This will quit the daemon when a signal is received
//function signalHandler($signal) use (&$iterationCycle) {
function signalHandler($signal) {
  global $iterationCycle;
  $logger2 = new Logger(basename(__FILE__), 0, $iterationCycle);
  $logger2->info("Daemon shutdown");
  global $pidFile;
  global $pid;
  ftruncate($pidFile, 0);
  exit;
}
?>
