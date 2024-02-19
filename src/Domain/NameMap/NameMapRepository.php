<?php
declare(strict_types=1);

namespace App\Domain\NameMap;

/* NameMap is simple enough.  Kept in seporate table so we have
   can make pretty values when they are defined to make a site
   more readable.
*/

interface NameMapRepository {
    public function findNameMap($nameRequest): array;
    public function findAllNameMap();
    public function setNameMap($nameRequest): array;
    public function updateNameMap($nameRequest): array;
    public function deleteNameMap($nameRequest): array;
    public function invalidNameMap($nameRequest): array;
}
