<?php
declare(strict_types=1);

namespace App\Domain\EventCorrelation;

use App\Domain\DomainException\DomainRecordNotFoundException;

class EventNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The id you requested does not exist.';
}
