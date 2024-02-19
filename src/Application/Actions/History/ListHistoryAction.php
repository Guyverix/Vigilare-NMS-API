<?php
declare(strict_types=1);

namespace App\Application\Actions\History;

use Psr\Http\Message\ResponseInterface as Response;

class ListHistoryAction extends HistoryAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $events = $this->historyRepository->findAll();
        $this->logger->info("Event history full list was called.");
        return $this->respondWithData($events);
    }
}
