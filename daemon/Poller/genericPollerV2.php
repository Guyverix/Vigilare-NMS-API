#!/usr/bin/env php
<?php
// Vigilare Poller Daemon - Refactored Version
// Handles SNMP, NRPE, Shell, and Ping checks with modular, fault-tolerant logic

declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
declare(ticks=1);

require_once __DIR__ . "/../../app/config.php";
require_once __DIR__ . "/../../app/Curl.php";
require_once __DIR__ . "/../../app/Logger.php";
require_once __DIR__ . "/../../src/Infrastructure/Shared/ForkMonitor/Class/fork_daemon.php";
require_once __DIR__ . "/../../src/Infrastructure/Shared/Functions/daemonFunctions.php";

$cliOptions = getopt("i:s:t:");
$iterationCycle = isset($cliOptions['i']) ? (int)$cliOptions['i'] : null;
$monitorType = strtolower($cliOptions['t'] ?? 'snmp');
$daemonState = $cliOptions['s'] ?? 'start';

if (empty($iterationCycle)) {
  fwrite(STDERR, "FATAL: -i is a mandatory parameter for your iteration cycle\n");
  exit(1);
}

$logger = new ExternalLogger("{$monitorType}Poller", $daemonLog[$monitorType] ?? $defaultDaemonLog, $iterationCycle);

// Daemon signal support
pcntl_async_signals(true);
pcntl_signal(SIGTERM, 'signalHandler');
pcntl_signal(SIGHUP, 'signalHandler');
pcntl_signal(SIGINT, 'signalHandler');

// Old style keeps pid in this dir.  New version uses /tmp
//$pidFilePath = "{$monitorType}Poller.{$iterationCycle}.pid";
$pidFilePath = "/tmp/{$monitorType}Poller.{$iterationCycle}.pid";
if (file_exists($pidFilePath)) {
  $existingPid = trim(file_get_contents($pidFilePath));
  if ($daemonState === 'stop') {
    echo "Stopping daemon {$monitorType}Poller pid {$existingPid}\n";
    posix_kill((int)$existingPid, SIGTERM);
    unlink($pidFilePath);
    exit(0);
  } else {
    $logger->error("Daemon already running for {$monitorType}Poller (PID $existingPid)");
    exit(1);
  }
} elseif ($daemonState === 'stop') {
  echo "No active daemon to stop for {$monitorType}Poller\n";
  exit(0);
}

file_put_contents($pidFilePath, getmypid());

$logger->info("Starting {$monitorType}Poller (PID " . getmypid() . ") with interval {$iterationCycle}s");

$daemon = new fork_daemon();
$daemon->max_children_set($maxChildren[$monitorType] ?? 10);
$daemon->max_work_per_child_set($maxWork[$monitorType] ?? 100);
$daemon->register_child_run("process_child_run");
$daemon->register_parent_child_exit("process_child_exit");
$daemon->register_logging("logger", fork_daemon::LOG_LEVEL_ALL);
$daemon->register_parent_results("process_results");
$daemon->store_result_set(true);

while (true) {
  try {
    heartBeat("{$monitorType}Poller", $iterationCycle, getmypid());

    $activeEvents = json_decode(pullActiveEvents(), true);
    $monitors = json_decode(pullMonitors($monitorType, $iterationCycle), true);
    $workQueue = buildMonitorList($monitors['data'] ?? []);

    if (empty($workQueue)) {
      $logger->info("No monitors found. Sleeping {$iterationCycle}s");
      sleep($iterationCycle);
      continue;
    }

    $logger->info("Processing " . count($workQueue) . " monitor jobs");
    job_blocking();
    
    $results = $daemon->get_all_results();
    foreach ($results as $result) {
      if (!isset($result['output'], $result['exitCode'])) {
        $logger->warning("Missing output or exitCode for monitor: " . json_encode($result));
        continue;
      }
      clearEvents($activeEvents, $result);
      sendEvents($result);
      storageForward($result);
    }

    reportMetrics($monitorType, $iterationCycle, $monitors['data'] ?? [], $workQueue);
  } catch (Throwable $e) {
    $logger->error("Unhandled exception: " . $e->getMessage());
  }

  sleep($iterationCycle);
}

function process_child_run(array $dataset, string $id = ''): array {
  global $logger;
  $job = $dataset[0];

  $hostname = $job['hostProperties']['hostname'] ?? 'unknown';
  $address = $job['hostProperties']['address'] ?? '';
  $checkName = $job['checkName'] ?? '';
  $checkAction = $job['checkAction'] ?? '';
  $type = strtolower($job['type'] ?? 'unknown');

  try {
    if ($type !== 'alive' && ($job['hostProperties']['isAlive'] ?? '') === 'dead') {
      $logger->warning("Skipping $type check on dead host: $hostname");
      return [];
    }

    $result = match ($type) {
      'nrpe'  => shellNrpeCheck($job),
      'snmp', 'get', 'walk' => shellSnmpCheck($job),
      'ping'  => shellPingCheck($job),
      'alive' => shellAliveCheck($job),
      'shell' => shellCommandCheck($job),
      default => [
        'output' => "Unknown check type: $type",
        'exitCode' => 2,
        'command' => 'invalid-type'
      ]
    };

    return [
      'hostname' => $hostname,
      'address' => $address,
      'checkName' => $checkName,
      'storage' => $job['storage'] ?? '',
      'checkAction' => $checkAction,
      'type' => $type,
      'output' => json_encode($result['output'] ?? '', 1),
      'exitCode' => $result['exitCode'] ?? 2,
      'command' => $result['command'] ?? 'unknown'
    ];
  } catch (Throwable $e) {
    $logger->error("Child failure for $hostname/$checkName: " . $e->getMessage());
    return [
      'hostname' => $hostname,
      'checkName' => $checkName,
      'output' => $e->getMessage(),
      'exitCode' => 2,
      'command' => 'exception'
    ];
  }
}

function reportMetrics(string $type, int $cycle, array $monitors, array $jobs): void {
  global $pollerName, $logger;
  $metrics = [
    'Monitors' => count($monitors),
    'Devices'  => count($jobs),
    'Time'     => $cycle,
    'Memory'   => memory_get_usage(true),
  ];
  $sent = sendPollerPerformance($pollerName, json_encode($metrics, 1), "$type-$cycle", "poller", null, null);
  $logger->info($sent === 1 ? "Saved poller metrics" : "Failed to save poller metrics");
}

function buildMonitorList(array $monitors): array {
  $expanded = [];
  foreach ($monitors as $monitor) {
    foreach ($monitor['hostProperties'] ?? [] as $host) {
      $expanded[] = [
        'id' => $monitor['id'],
        'checkName' => $monitor['checkName'],
        'checkAction' => $monitor['checkAction'],
        'type' => $monitor['type'],
        'storage' => $monitor['storage'],
        'hostProperties' => $host,
      ];
    }
  }
  return $expanded;
}

?>
