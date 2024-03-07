<?php
declare(strict_types=1);

namespace App\Domain\EventCorrelation;

require __DIR__ . '/../../../app/Database.php';

interface EventCorrelationRepository {
    // CREATE
    public function createRule($arr);
    public function createEceGroups($arr);

    // RETRIEVE / VIEW
    public function findRule();

//    public function searchRule($arr);
//    public function searchRuleAnd($arr);
//    public function searchRuleOr($arr);

    public function familyRule();

    // UPDATE
    public function updateRule($arr);

    // DELETE
    public function deleteRule($id);
}
