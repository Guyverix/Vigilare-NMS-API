<?php
declare(strict_types=1);

namespace App\Application\Actions\History;
use Psr\Http\Message\ResponseInterface as Response;
/*
  This API does not specifically have a CREATE, as
  we are dealing with events that are created already
  We do have support for deleting something if the need
  arises, but this should be both uncommon and tightly
  controlled in the route
*/


class ManageHistoryAction extends HistoryAction {
    /**
     * {@inheritdoc}
     */
  protected function action(): Response  {
    $action=$this->resolveArg("action");

    switch ($action) {
    case "view" :
      $column = (string) $this->resolveArg('column');
      $direction = (string) $this->resolveArg('direction');
      $filter = (string) $this->resolveArg('filter');
      $event = $this->historyRepository->findColumnDirectionOfHistory($column, $direction, $filter);
      $this->logger->info("Events retrieved where db column ${column} ${direction} $filter were retrieved.");
      return $this->respondWithData($event);
      break;
    case "findId":
      $id=$this->resolveArg('column');
      $this->logger->info("Query history for event id ${id}");
      $event = $this->historyRepository->findHistoryOfId($id);
      break;
    case "viewTable":
      $table = $this->resolveArg('column');
      $event = $this->historyRepository->findTableNames($table);
      $this->logger->info("View Table structure in ${table}");
      break;
    case "viewAll":
      $event = $this->historyRepository->findAll();
      $this->logger->info("Find all historical events");
      break;
    case "viewLimit":
      $column= (int) $this->resolveArg("column");
      $event = $this->historyRepository->findLimit((int) $column);
      $this->logger->info("Find limited number of historical events");
      break;
    case "countHistoryAllHostsSeen":
      $event = $this->historyRepository->countHistoryAllHostsSeen();
      $this->logger->info("Count the total number of any devices seen in event OR history tables.");
      break;
    case "historyEventCount":
      $event = $this->historyRepository->historyEventCount();
      $this->logger->info("Count the number of events in the history table.");
      break;
    case "delete":
      $evid = resolveArg('column');
      $event = $this->historyRepository->delete($evid);
      $this->logger->warning("Call given to delete specific event from database " . $evid);
    default:
      $event = "No valid action called.  Try: view, viewTable, viewAll, findId, countHistoryAllHostsSeen, historyEventCount";
      $this->logger->warning("Route called with no valid action set in URL.");
      return $this->respondWithData($event);
      break;
    } // end switch
  return $this->respondWithData($event);
  }  // end function
}  // end class
