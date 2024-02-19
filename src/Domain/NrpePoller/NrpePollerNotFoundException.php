<?php
declare(strict_types=1);

namespace App\Domain\NrpePoller;

use App\Domain\DomainException\DomainRecordNotFoundException;

class NrpePollerNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The Nrpe Poller you requested does not exist.';
}
