<?php
declare(strict_types=1);

namespace App\Domain\Authentication;

use App\Domain\DomainException\DomainRecordNotFoundException;

class AuthenticationNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Authentication function not called correctly.';
}
