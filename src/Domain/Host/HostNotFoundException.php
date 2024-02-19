<?php
declare(strict_types=1);

namespace App\Domain\Host;

use App\Domain\DomainException\DomainRecordNotFoundException;

class HostNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The host you requested does not exist.';
}
