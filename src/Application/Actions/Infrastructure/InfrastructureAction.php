<?php
declare(strict_types=1);

namespace App\Application\Actions\Infrastructure;

use App\Application\Actions\Action;
use App\Domain\Infrastructure\InfrastructureRepository;
use Psr\Log\LoggerInterface;

abstract class InfrastructureAction extends Action
{
    /**
     * @var InfrastructureRepository
     */
    protected $infrastructureRepository;

    /**
     * @param LoggerInterface $logger
     * @param InfrastructureRepository $infrastructureRepository
     */
    public function __construct(LoggerInterface $logger, InfrastructureRepository $infrastructureRepository) {
        parent::__construct($logger);
        $this->infrastructureRepository = $infrastructureRepository;
    }
}
