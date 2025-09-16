<?php
declare(strict_types=1);

namespace App\Domain\Monitors;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// CRUD  Create Retrieve Update Delete
interface MonitorsRepository {

    // Create Monitors stuff
    public function createMonitor($arr): array;               // Create a new monitor                * device and hostgroup optional

    // Retrieve Information of some kind
    public function findMonitorNames();                       // returns array of all rows MATCHING monitoring type in Monitors
    public function findMonitors();                           // returns array of all rows MATCHING monitoring type in Monitors
    public function findMonitorsDisable();                    // returns array of non-monitored rows (unused right now)
    public function findMonitorsAll();                        // returns array of all rows in Monitors
    public function findMonitorsByCheckName($arr): array;     // return all checkNames associated with id
    public function findMonitorIteration();                   // Return all valid iteration timers (60,300)
    public function findMonitorstorage();                     // Return valid storage types        (debug,database,databaseMetric,rrd,graphite)
    public function findMonitorType();                        // Return valid types of monitors    (get,walk,nrpe)
    public function findDeviceId();                           // Return device id, name, ip address
    public function findHostGroup();                          // Return list of hostgroups
    public function findAlarmCount();                         // Return list of alarms

    public function findMonitorsByHostId($arr): array;        // Return list of monitors that a specific host is using

    // Update Monitor with some change
    public function updateMonitor($arr): array;               // Change settings on an existing monitor
    public function monitorAddHost($arr): array;              // Add a hostid to existing monitor    * one or more hosts in csv
    public function monitorAddHostgroup($arr): array;         // Add a hostGroup to existing monitor * one or more hostgroups in csv

    // Delete Monitors?
    public function deleteMonitor($id);                       // nuke an entire monitor               * one only
    public function monitorDeleteHost($arr): array;           // Remove single host from monitor      * one or more
    public function monitorDeleteHostgroup($arr): array;      // remove hostgroup from monitor        * one or more
}
