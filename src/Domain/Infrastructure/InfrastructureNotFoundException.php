<?php
declare(strict_types=1);

namespace App\Domain\Infrastructure;

use App\Domain\DomainException\DomainRecordNotFoundException;

class InfrastructureNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The infrastructure path you requested does not exist.';
}
