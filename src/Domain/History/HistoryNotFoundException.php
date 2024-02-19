<?php
declare(strict_types=1);

namespace App\Domain\History;

use App\Domain\DomainException\DomainRecordNotFoundException;

class HistoryNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The history you requested does not exist.';
}
