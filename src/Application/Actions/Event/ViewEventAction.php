<?php
declare(strict_types=1);

namespace App\Application\Actions\Event;

use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;

class ViewEventAction extends EventAction {
    /**
     * {@inheritdoc}
     */
  protected function action(): Response  {
    $jobType=["findAliveTime", "findHistoryTime", "findEventTime", "view", "findId", "viewTable", "viewAll", "countEventAllHostsSeen", "activeEventCount", "activeEventCountList","historyEventCount", "countEventEventHostsSeen", "monitorList", "ageOut", "moveToHistory", "moveFromHistory", "findActiveEventByHostname", "findClosedEventByHostname", "findHistoryEventByDeviceId", "findActiveEventByDeviceId"];
    if ( empty($this->args["action"]) ) { $action="failure";} else { $action=$this->resolveArg("action"); }

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage MonitoringPoller Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    $data = $this->getFormData();

    switch ($action) {

    case 'view':
      $column = (string) $this->resolveArg('column');
      $direction = (string) $this->resolveArg('direction');
      $filter = (string) $this->resolveArg('filter');
      if ($filter == "order") {
        $event = $this->eventRepository->findSortedEvents($column, $direction);
        $this->logger->info("Events retrieved where db column $column was sorted as $direction.");
      }
      else {
        $event = $this->eventRepository->findColumnDirectionOfEvent($column, $direction, $filter);
        $this->logger->info("Events retrieved where db column $column $direction $filter were retrieved.");
      }
      break;
    case 'findId':
      $id=$this->resolveArg('column');
      $this->logger->info("Query event for event id " . $id);
      $event = $this->eventRepository->findEventOfId($id);
      break;
    case 'findActiveEventByHostname':   // GET
      $id=$this->resolveArg('column');
      $this->logger->info("Query event active events by hostname: " . $id);
      $event = $this->eventRepository->findActiveEventByHostname($id);
      break;
    case 'findClosedEventByHostname':   // GET
      $id=$this->resolveArg('column');
      $this->logger->info("Query history for event hostname " . $id);
      $event = $this->eventRepository->findClosedEventByHostname($id);
      break;
    case 'findActiveEventByDeviceId':   // GET
      $id=$this->resolveArg('column');
      $this->logger->info("Query history for event by device id " . $id);
      $event = $this->eventRepository->findActiveEventByDeviceId($id);
      break;
    case 'findHistoryEventByDeviceId':   // GET
      $id=$this->resolveArg('column');
      $this->logger->info("Query history for event id " . $id);
      $event = $this->eventRepository->findHistoryEventByDeviceId($id);
      break;

    case 'viewTable':
      $table = $this->resolveArg('column');
      $event = $this->eventRepository->findTableNames($table);
      $this->logger->info("View Table structure in " . $table);
      break;
    case 'viewAll':
      $event = $this->eventRepository->findAll();
      $this->logger->info("Find all events called");
      break;
    case 'countEventAllHostsSeen':
      $event = $this->eventRepository->countEventAllHostsSeen();
      $this->logger->info("Count the number of any devices seen in event OR history tables.");
      break;
    case 'activeEventCount':
      $event = $this->eventRepository->activeEventCount();
      $this->logger->info("Count the number of events in the event  table.");
      break;
    case 'activeEventCountList': // Will return 0 = #, 1 = # ... 5 = # in the event table
      $event = $this->eventRepository->activeEventCountList();
      $this->logger->info("Count the number of events in the event  table.");
      break;
    case 'historyEventCount':
      $event = $this->eventRepository->historyEventCount();
      $this->logger->info("Count the number of events in the history table.");
      break;
    case 'countEventEventHostsSeen':
      $event = $this->eventRepository->countEventEventHostsSeen();
      $this->logger->info("Count number of unique device in the events table.");
      break;
    case 'monitorList':
      $event = $this->eventRepository->monitorList();
      $this->logger->debug("Return list of active device and eventNames.");
      break;
    case 'ageOut':
      $event = $this->eventRepository->ageOut();
      $this->logger->debug("Return list of events to ageOut.");
      break;
    case 'moveToHistory':
      $id = $data['id'];
      $reason = $data['reason'];
      $event = $this->eventRepository->moveToHistory($id,$reason);
      $this->logger->debug("Move " . $id . " to history because: " . $reason);
      break;
    case 'moveFromHistory':
      $id = $data['id'];
      $reason = $data['reason'];
      $event = $this->eventRepository->moveFromHistory($id,$reason);
      $this->logger->debug("Move " . $id . " to history because: " . $reason);
      break;
    case 'findHistoryTime':
      $event = $this->eventRepository->findHistoryTime($data);
      $this->logger->debug("Calc history event time " . $data['id']);
      break;
    case 'findEventTime':
      $event = $this->eventRepository->findEventTime($data);
      $this->logger->debug("Calc active event time " . $data['id']);
      break;
    case 'findAliveTime':
      $event = $this->eventRepository->findAliveTime($data);
      $this->logger->debug("Calc ping down time " . $data['id']);
      break;
    default:
      $event = "No valid action called.  Try: view, viewTable, viewAll, findId, countEventAllHostsSeen, countEventEventHostsSeen";
      $this->logger->warning("Route called with no action set in URL.");
      return $this->respondWithData($event);
      break;
    }
    return $this->respondWithData($event);
  }
}
