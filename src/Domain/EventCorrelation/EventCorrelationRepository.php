<?php
declare(strict_types=1);

namespace App\Domain\EventCorrelation;

require __DIR__ . '/../../../app/Database.php';

interface EventCorrelationRepository {
    // CREATE
    public function createRule($arr);

    // RETRIEVE / VIEW
    public function findRule();

    // UPDATE
    public function updateRule($arr);

    // DELETE
    public function deleteRule($id);
}
