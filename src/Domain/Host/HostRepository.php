<?php
declare(strict_types=1);

namespace App\Domain\Host;

// Following my verion of CRUD: Create, Retrieve, Update, Delete

interface HostRepository {
    public function createHost($array);
    public function updateHost($array);
    public function deleteHost($array);
    public function deleteHostId($array);
    public function updateEvents($array);  // Update the event database with changed hostname

    public function findAllHost();         // all Hosts returned
    public function findHost($array);      // sinlge Host returned
    public function findAddress($array);   // sinlge Host returned

    public function findPerformance($array); // Get performance values for given hostname
    public function findAttribute($array);   // Get host attributes for given hostname
}
