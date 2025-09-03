<?php
declare(strict_types=1);

namespace App\Domain\Event;

require __DIR__ . '/../../../app/Database.php';

interface EventRepository {
    // CREATE

    // RETRIEVE / VIEW
    public function findAll(): array;
    public function findTableNames(string $table): array;
    public function findEventOfId(string $evid): array;
    public function findSingleEventOfId(string $evid): array;
    public function findColumnDirectionOfEvent(string $stateChange, string $direction, string $filter):array;
    public function findSortedEvents(string $column, string $direction): array;
    public function countEventAllHostsSeen();        // Distinct hosts seen in active events
    public function countEventEventHostsSeen();      // Distinct hosts seen in Active AND history event database
    public function activeEventCount();              // Number of Active events
    public function activeEventCountList();          // Event counts AND severity
    public function historyEventCount();             // Number of historical events
    public function monitorList();                   // Retrun list of device and eventName that is active
    public function findClosedEventByHostname($id);  // Return history events by hostname
    public function findActiveEventByHostname($id);  // Return active events by hostname
    public function findHistoryEventByDeviceId($id);
    public function findActiveEventByDeviceId($id);
    public function findHistoryTime($arr);           // Return number of minutes events were happening ( id, startTime)
    public function findEventTime($arr);             // Return number of minutes events were happening ( id, startTime)
    public function findAliveTime($arr);             // Return number of minutes events were happening ( id, startTime)
    public function findHotSpot($arr);               // Return top 5 list of events in a window of time
    public function findAppGroupDown();              // return up / down by id in applicationGroup table list

    // UPDATE
    public function ageOut();                        // Retrun list of ageOut events
    public function moveToHistory($id,$reason);      // Move id from event to history
    public function moveFromHistory($id,$reason);    // Move id from event to history

    // DELETE
}
