<?php
declare(strict_types=1);

namespace App\Domain\Discovery;

use App\Domain\DomainException\DomainRecordNotFoundException;

class DiscoveryNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Discovery function not called correctly.';
}
