<?php
declare(strict_types=1);

namespace App\Domain\MonitoringPoller;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MonitoringPollerNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'MonitoringPoller function not called correctly.';
}
