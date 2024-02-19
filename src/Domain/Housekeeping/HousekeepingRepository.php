<?php
declare(strict_types=1);

namespace App\Domain\Housekeeping;

/* This is called from /Action/Housekeeping/NewHousekeepingAction.php */
interface HousekeepingRepository {
    public function housekeepingIteration();  // Retrieve array of iteration cycles
    public function housekeepingStatus();     // start or status
    public function housekeepingRun();        // start and stop explicit
    public function housekeepingKill();       // stop and confirm
    public function housekeepingKnown();       // stop and confirm
}
