<?php
declare(strict_types=1);

namespace App\Domain\Housekeeping;

use App\Domain\DomainException\DomainRecordNotFoundException;

class HousekeepingNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The Housekeeping class you requested does not exist.';
}
