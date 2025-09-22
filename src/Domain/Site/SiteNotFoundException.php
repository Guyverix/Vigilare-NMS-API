<?php
declare(strict_types=1);

namespace App\Domain\Site;

use App\Domain\DomainException\DomainRecordNotFoundException;

class SiteNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The information you requested does not exist for this Domain.';
}
