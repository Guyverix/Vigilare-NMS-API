<?php
declare(strict_types=1);

namespace App\Domain\Device;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// Following my verion of CRUD: Create, Retrieve(view), Update, Delete
interface DeviceRepository {
    // create
    public function createHost($array);             // Will create a host
    public function createDeviceGroup($array);      // Will create a deviceGroup

    // retrieve
    public function propertiesHost($array);  // retrieve all properties associated with host from discovery
    public function findAllHost();           // all Hosts returned
    public function findId($array);          // sinlge Host returned
    public function findHost($array);        // sinlge Host returned
    public function findAddress($array);     // sinlge Host returned
    public function findPerformance($array); // Get performance values for given hostname
    public function findAttribute($array);   // Get host attributes for given hostname

    public function findDeviceGroupMonitors($array); // return all monitors associated that use a specific deviceGroup
    public function findDeviceGroup();               // return all defined deviceGroups
    public function findDeviceInDeviceGroup($array); // return deviceGroups which a device id is a member of

    // update
    public function updateProperties($array);  // just what it says on the box
    public function updateHost($array);        // change the value of an id in the device table
    public function updateDeviceGroup($array); // add or remove id values from hostname
    public function updateEvents($array);      // Update the event database with changed hostname

    // delete
    public function deleteDeviceGroup($array);
    public function deleteHost($array);
    public function deleteHostId($array);

}
