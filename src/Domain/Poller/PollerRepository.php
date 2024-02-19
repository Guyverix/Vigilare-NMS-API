<?php
declare(strict_types=1);

namespace App\Domain\Poller;
require __DIR__ . '/../../../app/Database.php';

/* This is called from /Action/Poller/NewPollerAction.php */
interface PollerRepository {
    public function statusPid($poller);        // start or status
    public function pollerIteration($poller);  // Retrieve array of iteration cycles
    public function pollerStatus($poller);     // start or status
    public function pollerRun($poller);        // start and stop explicit
    public function pollerKill($poller);       // stop and confirm
    public function pollerHeartbeat();         // Return heartbeat data/status
    public function pollerList();              // Return all pollers and iterations
}
