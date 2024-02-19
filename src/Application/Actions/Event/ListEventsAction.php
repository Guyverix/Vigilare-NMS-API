<?php
declare(strict_types=1);

namespace App\Application\Actions\Event;

use Psr\Http\Message\ResponseInterface as Response;

class ListEventsAction extends EventAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $events = $this->eventRepository->findAll(); 
        $this->logger->info("events list was viewed.");
        return $this->respondWithData($events);
    }
}
