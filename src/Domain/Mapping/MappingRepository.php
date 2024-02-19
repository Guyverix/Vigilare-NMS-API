<?php
declare(strict_types=1);

namespace App\Domain\Mapping;

// Following my verion of CRUD: Create, Retrieve, Update, Delete

interface MappingRepository {
    public function createMapping($array);
    public function updateMapping($array);
    public function deleteMapping($array);

    public function findAllOid();        // all oids returned
    public function findOid($array);     // sinlge oid returned
}
