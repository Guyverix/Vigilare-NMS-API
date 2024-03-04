<?php
declare(strict_types=1);

namespace App\Domain\EventCorrelation;

require __DIR__ . '/../../../app/Database.php';

interface EventCorrelationRepository {
    // CREATE
    public function newRule($arr);

    // RETRIEVE / VIEW
    public function findAll(): array;

    // UPDATE
    public function changeRule($arr);

    // DELETE
    public function deleteRule($id);

}
