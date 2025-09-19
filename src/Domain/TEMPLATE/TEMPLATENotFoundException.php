<?php
declare(strict_types=1);

namespace App\Domain\TEMPLATE;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TEMPLATENotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The information you requested does not exist for this Domain.';
}
