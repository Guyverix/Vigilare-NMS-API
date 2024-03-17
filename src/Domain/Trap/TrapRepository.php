<?php
declare(strict_types=1);

namespace App\Domain\Trap;

// Setup our database connections outside of the Persistence directory
require __DIR__ . '/../../../app/Database.php';

interface TrapRepository {
    /* Follow CRUD as best as possible */
    // create
    public function createEvent($arr): array;
    public function returnNew($arr): array;

    // retrieve
    public function returnHost($arr): array;
    public function returnMap($arr): array;
    public function returnPreMap($arr): array;
    public function returnPostMap($arr): array;

    // update
    public function useMapping($arr): array;
    public function postMapping($arr): array;

    // delete (never actually delete an event? Only move to history)
}
