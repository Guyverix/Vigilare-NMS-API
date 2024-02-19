<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Authentication;

use App\Domain\Authentication\Authentication;
use App\Domain\Authentication\AuthenticationNotFoundException;
use App\Domain\Authentication\AuthenticationRepository;
use Database;

class DatabaseAuthenticationRepository implements AuthenticationRepository {

  public function __construct() {
    $this->db = new Database();
  }

  private function updateRedis($arr): array {

  }

  private function expireRedis($arr): array {

  }

  private function insertRedis($arr): array {

  }

  public function login($arr): array {

  }

  public function logout($arr): array {

  }

} // End CLASS
