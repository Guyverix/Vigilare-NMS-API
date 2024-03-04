<?php
declare(strict_types=1);

namespace App\Application\Actions\Event;

use Psr\Http\Message\ResponseInterface as Response;

class ViewTableEventAction extends EventAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
//           $column = (string) $this->resolveArg('column');
        $tableName = (string) $this->resolveArg('table');
        $events = $this->eventRepository->findTableNames($tableName);
        $this->logger->info("Retrieved the columns name for table $tableName.");
        return $this->respondWithData($events);
    }
}
