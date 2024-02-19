<?php
declare(strict_types=1);

namespace App\Application\Actions\Event;

use App\Application\Actions\Action;
use App\Domain\Event\EventRepository;
use Psr\Log\LoggerInterface;

abstract class EventAction extends Action {
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @param LoggerInterface $logger
     * @param EventRepository $eventRepository
     */
    public function __construct(LoggerInterface $logger, EventRepository $eventRepository) {
        parent::__construct($logger);
        $this->eventRepository = $eventRepository;
    }
}
