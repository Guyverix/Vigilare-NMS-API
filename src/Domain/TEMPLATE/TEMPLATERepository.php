<?php
/*
  This is simply a template to go between
  the Action and the Database for return data
  Additionally, you should be able to autowire this into
  other things if needed

  Change out all functions for ones you have defined
*/

declare(strict_types=1);

namespace App\Domain\TEMPLATE;

require __DIR__ . '/../../../app/Database.php';

/*
  In normal cases these functions will match the ones
  defined in your Database in Infrastructure/Persistence/TEMPLATE/DatabaseTEMPLATERepository.php
*/

interface TEMPLATERepository {
    public function findAll(): array;
    public function findSomethingOfId((int) $id): TEMPLATE;
    public function doSomething((array)$arr): array;
}
