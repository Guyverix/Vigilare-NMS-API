<?php
declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\DomainException\DomainRecordNotFoundException;

class EventNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The event you requested does not exist.';
}
