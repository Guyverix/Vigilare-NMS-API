<?php
declare(strict_types=1);

namespace App\Domain\Poller;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PollerNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The Poller you requested does not exist.';
}
