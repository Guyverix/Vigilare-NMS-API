<?php
declare(strict_types=1);

namespace App\Domain\Monitors;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MonitorsNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Monitors class or function not called correctly.';
}
