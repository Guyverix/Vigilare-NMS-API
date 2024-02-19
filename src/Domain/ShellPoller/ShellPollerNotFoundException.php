<?php
declare(strict_types=1);

namespace App\Domain\ShellPoller;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ShellPollerNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The Shell Poller you requested does not exist.';
}
