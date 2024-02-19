<?php
declare(strict_types=1);

namespace App\Domain\InternalSnmp;

use App\Domain\DomainException\DomainRecordNotFoundException;

class InternalSnmpNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Snmp function not called correctly.';
}
