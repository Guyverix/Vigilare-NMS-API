<?php
declare(strict_types=1);

namespace App\Domain\Mapping;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MappingNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The mapping you requested does not exist.';
}
