<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventCorrelation;

use App\Domain\Event\EventCorrelation;
use App\Domain\Event\EventCorrleationNotFoundException;
use App\Domain\Event\EventCorrleationRepository;

use Database;

class DatabaseEventCorrelationRepository implements EventCorrelationRepository {
  public $db = "";

  public function __construct() {
    $this->db = new Database();
  }

  public function createRule($arr) {
    $this->db->prepare("INSERT INTO eventCorrleationEngine SET VALUES('', :appName , :appService, :appState, :serviceState, :serviceName, :appCorrelation)");
    $this->db->bind('appName', $arr['appName']);
    $this->db->bind('appService', $arr['appService']);
    $this->db->bind('appState', $arr['appState']);
    $this->db->bind('serviceState', $arr['serviceState']);
    $this->db->bind('serviceName', $arr['serviceName']);
    $this->db->bind('appCorrelation', $arr['appCorrelation']);
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    return "Event Corrleation Engine Rule " . $arr['id'] . " has been updated";
  }

  public function updateRule($arr) {
    $this->db->prepare("UPDATE eventCorrleationEngine SET appName= :appName , appService= :appService, appState= :appState, serviceState= :serviceState, serviceName= :serviceName, appCorrleation= :appCorrelation WHERE id= :id");
    $this->db->bind('appName', $arr['appName']);
    $this->db->bind('appService', $arr['appService']);
    $this->db->bind('appState', $arr['appState']);
    $this->db->bind('serviceState', $arr['serviceState']);
    $this->db->bind('serviceName', $arr['serviceName']);
    $this->db->bind('appCorrelation', $arr['appCorrelation']);
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    return "Event Corrleation Engine Rule " . $arr['id'] . " has been updated";
  }

  public function deleteRule($id) {
    $this->db->prepare("DELETE FROM eventCorrleationEngine WHERE id= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    return "Event Correlation Egnine Rule " . $id . " has been deleted"";
  }

  public function findAll(): array {
    $this->db->prepare("SELECT * FROM eventCorrelationEngine ORDER BY id ASC");
    $data = $this->db->resultset();
    return array_values($data);
  }
}
