<?php
declare(strict_types=1);

namespace App\Application\Actions\History;

use Psr\Http\Message\ResponseInterface as Response;

class ViewHistoryAction extends HistoryAction {
    /**
     * {@inheritdoc}
     */
  protected function action(): Response  {

    $action=$this->resolveArg("action");

    if ($action == "view" ) {
      $column = (string) $this->resolveArg('column');
      $direction = (string) $this->resolveArg('direction');
      $filter = (string) $this->resolveArg('filter');
      $event = $this->historyRepository->findColumnDirectionOfHistory($column, $direction, $filter);
      $this->logger->info("Events retrieved where db column ${column} ${direction} $filter were retrieved.");
      return $this->respondWithData($event);
    }
    elseif ($action == "findId") {
      $id=$this->resolveArg('column');
      $this->logger->info("Query history for event id ${id}");
      $event = $this->historyRepository->findHistoryOfId($id);
      return $this->respondWithData($event);
    }

    elseif ($action == "viewTable") {
      $table = $this->resolveArg('column');
      $event = $this->historyRepository->findTableNames($table);
      $this->logger->info("View Table structure in ${table}");
      return $this->respondWithData($event);
    }

    elseif ($action == "viewAll") {
      $event = $this->historyRepository->findAll();
      $this->logger->info("Find all historical events");
      return $this->respondWithData($event);
    }

    elseif ($action == "viewLimit") {
      $column= (int) $this->resolveArg("column");
      $event = $this->historyRepository->findLimit((int) $column);
      $this->logger->info("Find limited number of historical events");
      return $this->respondWithData($event);
    }

    elseif ($action == "countHistoryAllHostsSeen") {
      $event = $this->historyRepository->countHistoryAllHostsSeen();
      $this->logger->info("Count the total number of any devices seen in event OR history tables.");
      return $this->respondWithData($event);
    }

    elseif ($action == "historyEventCount") {
      $event = $this->historyRepository->historyEventCount();
      $this->logger->info("Count the number of events in the history table.");
      return $this->respondWithData($event);
    }

    else {
      $event = "No valid action called.  Try: view, viewTable, viewAll, findId, countHistoryAllHostsSeen, historyEventCount";
      $this->logger->warning("Route called with no action set in URL.");
      return $this->respondWithData($event);
    }

  }
}
