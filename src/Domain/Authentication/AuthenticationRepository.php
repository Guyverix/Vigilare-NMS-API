<?php
declare(strict_types=1);

namespace App\Domain\Authentication;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// This route does NOT follow CRUD.  Login Logout only
interface AuthenticationRepository {
  public function login($data): array;
  public function logout($data): array;
}
