<?php
declare(strict_types=1);

namespace App\Domain\ShellPoller;

/* This is called from /Action/ShellPoller/NewShellPollerAction.php */
interface ShellPollerRepository {
    public function statusPid();        // start or status
    public function pollerIteration();  // Retrieve array of iteration cycles
    public function pollerStatus();     // start or status
    public function pollerRun();        // start and stop explicit
    public function pollerKill();       // stop and confirm
}
