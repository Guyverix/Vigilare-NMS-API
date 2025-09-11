#!/usr/bin/env php
<?php
// Vigilare Poller Daemon (Refactored)

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/Curl.php';
require_once __DIR__ . '/../../app/Logger.php';
require_once __DIR__ . '/../../src/Infrastructure/Shared/ForkMonitor/Class/fork_daemon.php';
require_once __DIR__ . '/../../src/Infrastructure/Shared/Functions/daemonFunctions.php';

//use function pcntl_async_signals;
//use function pcntl_signal;

declare(ticks = 1);

$cliOptions = getopt("i:s:t:");
$iterationCycle = isset($cliOptions['i']) ? (int)$cliOptions['i'] : null;
$daemonState = $cliOptions['s'] ?? 'start';
$monitorType = strtolower($cliOptions['t'] ?? 'snmp');

if (!$iterationCycle) {
    echo "FATAL: -i is a mandatory parameter for your iteration cycle\n";
    exit(1);
}

$pollerName = 'larvel01.iwillfearnoevil.com';
$daemonPid = getmypid();

$logSeverity = $daemonLog[$monitorType] ?? $defaultDaemonLog;
$logger = new ExternalLogger("{$monitorType}Poller", $logSeverity, $iterationCycle);

pcntl_async_signals(true);
pcntl_signal(SIGTERM, 'signalHandler');
pcntl_signal(SIGHUP, 'signalHandler');
pcntl_signal(SIGINT, 'signalHandler');

$defaultMaxWork = $maxWork[$monitorType] ?? 10;
$defaultMaxChildren = $maxChildren[$monitorType] ?? 5;

$server = new fork_daemon();
$server->max_children_set($defaultMaxChildren);
$server->max_work_per_child_set($defaultMaxWork);
$server->register_child_run("process_child_run");
$server->register_parent_child_exit("process_child_exit");
$server->register_logging("logger", fork_daemon::LOG_LEVEL_ALL);
$server->register_parent_results("process_results");
$server->store_result_set(true);

//$daemonPidFileName = "{$monitorType}Poller.{$iterationCycle}.pid";
$daemonPidFileName = "/tmp/{$monitorType}Poller.{$iterationCycle}.pid";
$daemonPidFile = @fopen($daemonPidFileName, 'c');

if (!$daemonPidFile || !@flock($daemonPidFile, LOCK_EX | LOCK_NB)) {
    $existingPid = file_get_contents($daemonPidFileName);
    if ($daemonState === 'stop') {
        echo "Stopping daemon {$monitorType}Poller pid {$existingPid}\n";
        exec("kill -15 $existingPid &>/dev/null");
        ftruncate($daemonPidFile, 0);
        exit(0);
    }
    echo "Daemon already running for {$monitorType}Poller pid: {$existingPid}\n";
    exit(1);
} elseif ($daemonState === 'stop') {
    ftruncate($daemonPidFile, 0);
    $logger->warning("Stop called for {$monitorType}Poller but no recorded pid running");
    echo "No recorded pid running for {$monitorType}Poller\n";
    exit(0);
}

$existingPid = file_get_contents($daemonPidFileName);
if (!empty($existingPid) && file_exists("/proc/{$existingPid}")) {
    $logger->error("Daemon already running with PID {$existingPid}");
    exit(1);
}

ftruncate($daemonPidFile, 0);
fwrite($daemonPidFile, (string)$daemonPid);

echo "Starting daemon {$monitorType}Poller pid {$daemonPid}\n";
$logger->info("Daemon started for {$monitorType}Poller with PID {$daemonPid}");

$sleepDate = time();

while (true) {
    heartBeat("{$monitorType}Poller", $iterationCycle, $daemonPid);

    $activeEvents = json_decode(pullActiveEvents(), true);
    $eventCount = count($activeEvents['data'] ?? []);
    $logger->debug("Active events: {$eventCount}");

    $pullMonitors = json_decode(pullMonitors($monitorType, $iterationCycle), true);
    $monitors = $pullMonitors['data'] ?? [];
    $logger->info("Monitors found: " . count($monitors));

    $monitorListHostDetails = getMonitorHostDetails($monitors);

    // Missing Part: Rebuild full task queue per-host per-monitor
    $expandedList = [];
    foreach ($monitorListHostDetails as $monitorExpanded) {
        foreach ($monitorExpanded['hostProperties'] as $singleHostProperties) {
            $expandedList[] = [
                'id' => $monitorExpanded['id'],
                'checkName' => $monitorExpanded['checkName'],
                'checkAction' => $monitorExpanded['checkAction'],
                'type' => $monitorExpanded['type'],
                'storage' => $monitorExpanded['storage'],
                'hostProperties' => $singleHostProperties
            ];
        }
    }
    $logger->debug("Expanded checks: " . count($expandedList));

    // Process work
    job_blocking();
    $results = $server->get_all_results();

    foreach ($results as $result) {
        if (isset($result['output'], $result['exitCode'])) {
            clearEvents($activeEvents, $result);
            sendEvents($result);
            storageForward($result);
        } else {
            $logger->warning("Missing output/exitCode for {$result['hostname']} check {$result['checkName']}");
        }
    }

    // Post-loop logging
    $timeNow = time();
    $delta = $timeNow - $sleepDate;
    $memoryUsage = memory_get_usage(true);

    $logger->info("Cycle complete in {$delta}s | Mem: {$memoryUsage} bytes");

    $pollerMetric = [
        'Monitors' => count($monitors),
        'Devices' => count($expandedList),
        'Time' => $delta,
        'Memory' => $memoryUsage
    ];

    $sent = sendPollerPerformance($pollerName, json_encode($pollerMetric), "{$monitorType}-{$iterationCycle}", "poller", null, null);
    if ($sent === 1) {
        $logger->error("Failed to send performance metrics");
    }

    $sleepTime = max(1, $iterationCycle - $delta);
    sleep($sleepTime);
    $sleepDate = time();
}
