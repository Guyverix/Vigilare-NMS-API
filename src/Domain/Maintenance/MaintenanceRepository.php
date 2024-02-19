<?php
declare(strict_types=1);

namespace App\Domain\Maintenance;

/* Maintenance is simple enough.  Kept in seporate table so we have
   a history of when things have been put in maintenance mode.
   So do not delete old maintenance, just end events.

   An end is global for a device, a set can be done against a component
   as well as the device alone.

   Find will generally be used for reporting and history.
*/

interface MaintenanceRepository {
    public function setMaintenance($maintenanceRequest): array;
    public function endMaintenance($maintenanceRequest): array;
    public function findMaintenanceDevice($maintenanceRequest): array;
    public function findMaintenanceComponent($maintenanceRequest): array;
    public function findMaintenanceStart($maintenanceRequest): array;
    public function findMaintenanceEnd($maintenanceRequest): array;
    public function findMaintenanceInvalid($maintenanceRequest): array;
}
