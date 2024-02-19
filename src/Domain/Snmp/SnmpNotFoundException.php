<?php
declare(strict_types=1);

namespace App\Domain\Snmp;

use App\Domain\DomainException\DomainRecordNotFoundException;

class SnmpNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Snmp function not called correctly.';
}
