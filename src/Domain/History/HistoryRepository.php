<?php
declare(strict_types=1);

namespace App\Domain\History;

interface HistoryRepository
{
    public function findAll(): array;
    public function findTableNames(string $table): array;
    public function findHistoryOfId(string $evid): array;
    public function findSingleHistoryOfId(string $evid): array;
    public function findColumnDirectionOfHistory(string $stateChange, string $direction, string $filter):array;
    public function countHistoryAllHostsSeen();      // Distinct hosts seen in active events
    public function countHistoryHistoryHostsSeen();    // Distinct hosts seen in Active AND history event database
//    public function activeHistoryCount();            // Number of Active events
    public function historyEventCount();           // Number of historical events
    public function findLimit(int $limit): array;

//    public function findEventOfId(string $evid): Event;
//    public function findEventOfId($evid);
//    public function findStateChangeBeforeOfEvent(string $stateChange):array;
//    public function findStateChangeAfterOfEvent(string $stateChange):array;
//    public function findStateChangeBeforeOfEvent(string $stateChange):array;
}
