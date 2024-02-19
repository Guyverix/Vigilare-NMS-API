<?php
declare(strict_types=1);

namespace App\Application\Actions\NameMap;

use App\Application\Actions\Action;
use App\Domain\NameMap\NameMapRepository;
use Psr\Log\LoggerInterface;

abstract class NameMapAction extends Action {
    protected $namemapRepository;

    public function __construct(LoggerInterface $logger, NameMapRepository $namemapRepository) {
        parent::__construct($logger);
        $this->namemapRepository = $namemapRepository;
    }
}
