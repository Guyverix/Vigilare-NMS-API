<?php
declare(strict_types=1);

namespace App\Domain\Trap;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TrapNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The trap you requested does not exist.';
}
