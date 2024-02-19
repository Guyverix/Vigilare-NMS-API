<?php
declare(strict_types=1);

namespace App\Domain\GlobalMapping;

use App\Domain\DomainException\DomainRecordNotFoundException;

class GlobalMappingNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The GlobalMapping type you requested does not exist.';
}
