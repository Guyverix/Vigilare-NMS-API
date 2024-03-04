<?php
declare(strict_types=1);

namespace App\Application\Actions\EventCorrelation;

use App\Application\Actions\Action;
use App\Domain\EventCorrelation\EventCorrelationRepository;
use Psr\Log\LoggerInterface;

abstract class EventCorrelationAction extends Action {
    /**
     * @var EventCorrelationRepository
     */
    protected $eventCorrelationRepository;

    /**
     * @param LoggerInterface $logger
     * @param EventCorrelationRepository $eventCorrelationRepository
     */
    public function __construct(LoggerInterface $logger, EventCorrelationRepository $eventCorrelationRepository) {
        parent::__construct($logger);
        $this->eventCorrelationRepository = $eventCorrelationRepository;
    }
}
