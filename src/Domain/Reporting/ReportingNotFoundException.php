<?php
declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ReportingNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The Reporting query you requested does not exist.';
}
