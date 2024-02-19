<?php
declare(strict_types=1);

namespace App\Domain\Validation;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ValidationNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Validation failed.';
}
