<?php
declare(strict_types=1);

namespace App\Domain\MonitoringPoller;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// CRUD  Create Retrieve Update Delete
interface MonitoringPollerRepository {

    // Create MonitoringPoller stuff

    // Retrieve Information of some kind
    public function FindMonitoringPoller($arr): array;        // returns array of all rows MATCHING monitoring type in MonitoringPoller
    public function FindMonitoringPollerDisable($arr): array; // returns array of non-monitored rows (unused right now)
    public function FindMonitoringPollerAll($arr): array;     // returns array of all rows in MonitoringPoller
    public function FindMonitoringHostname($arr): array;      // returns array of hostnames, addresses, production, states from Device table
    public function FindMonitoringId($arr): array;            // returns a list of id's that match against Device table
    public function FindMonitorsById($arr): array;            // return all checkNames associated with id
    public function housekeeping($arr): array;                // Return housekeeping data based on array options
    // Update MonitoringPoller?
    public function savePerformance($arr): array;             // Send metric data into the database
    public function saveHeartBeat($arr): array;               // save any heartbeat that calls this function
    public function saveAlive($arr): array;                   // Set if the host is alive or not.

    // Delete MonitoringPoller?
    public function deletePerformance($arr): array;           // Age out old performance database entries

    // Daemon controls?  Likely should be controlled elsewhere
/*
    public function stopMonitoringPoller($arr): array;
    public function startMonitoringPoller($arr): array;
    public function restartMonitoringPoller($arr): array;
*/
}
