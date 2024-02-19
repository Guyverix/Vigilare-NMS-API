<?php
declare(strict_types=1);

namespace App\Domain\Maintenance;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MaintenanceNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Maintenance function not called correctly.';
}
