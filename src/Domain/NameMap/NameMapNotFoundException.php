<?php
declare(strict_types=1);

namespace App\Domain\NameMap;

use App\Domain\DomainException\DomainRecordNotFoundException;

class NameMapNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'NameMap function not called correctly.';
}
