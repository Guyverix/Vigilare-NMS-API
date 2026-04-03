<?php
declare(strict_types=1);

namespace App\Domain\Metrics;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MetricsNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Metrics class or function not called correctly.';
}
