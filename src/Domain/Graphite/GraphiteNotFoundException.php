<?php
declare(strict_types=1);

namespace App\Domain\Graphite;

use App\Domain\DomainException\DomainRecordNotFoundException;

class GraphiteNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Graphite function not called correctly.';
}
