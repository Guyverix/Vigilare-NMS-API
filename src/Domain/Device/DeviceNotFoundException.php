<?php
declare(strict_types=1);

namespace App\Domain\Device;

use App\Domain\DomainException\DomainRecordNotFoundException;

class DeviceNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The device you requested does not exist.';
}
