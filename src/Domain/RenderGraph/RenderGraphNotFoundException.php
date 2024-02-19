<?php
declare(strict_types=1);

namespace App\Domain\RenderGraph;

use App\Domain\DomainException\DomainRecordNotFoundException;

class RenderGraphNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'RenderGraph function not called correctly.';
}
