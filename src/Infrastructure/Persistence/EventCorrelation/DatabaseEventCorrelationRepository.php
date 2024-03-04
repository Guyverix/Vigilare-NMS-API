<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventCorrelation;

use App\Domain\EventCorrelation\EventCorrelation;
use App\Domain\EventCorrelation\EventCorrelationNotFoundException;
use App\Domain\EventCorrelation\EventCorrelationRepository;

use Database;

class DatabaseEventCorrelationRepository implements EventCorrelationRepository {
  public $db = "";

  public function __construct() {
    $this->db = new Database();
  }

  private function searchRule($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  private function searchRuleAnd($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value AND :column2 :filter2 :value2");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $this->db->bind('column2', $arr['column2']);
    $this->db->bind('filter2', $arr['filter2']);
    $this->db->bind('value2', $arr['value2']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  private function searchRuleOr($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value OR :column2 :filter2 :value2");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $this->db->bind('column2', $arr['column2']);
    $this->db->bind('filter2', $arr['filter2']);
    $this->db->bind('value2', $arr['value2']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  public function createRule($arr) {
    $this->db->prepare("INSERT INTO eventCorrleationEngine SET VALUES('', :active, :appName , :appService, :appState, :serviceState, :serviceName, :appCorrelation, :eceSummary)");
    $this->db->bind('active', $arr['active']);
    $this->db->bind('appName', $arr['appName']);
    $this->db->bind('appService', $arr['appService']);
    $this->db->bind('appState', $arr['appState']);
    $this->db->bind('serviceState', $arr['serviceState']);
    $this->db->bind('serviceName', $arr['serviceName']);
    $this->db->bind('appCorrelation', $arr['appCorrelation']);
    $this->db->bind('eceSummary', $arr['eceSummary']);
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    return "Event Corrleation Engine Rule " . $arr['id'] . " has been updated";
  }

  public function updateRule($arr) {
    $this->db->prepare("UPDATE eventCorrleationEngine SET active= :active, appName= :appName , appService= :appService, appState= :appState, serviceState= :serviceState, serviceName= :serviceName, appCorrleation= :appCorrelation WHERE id= :id");
    $this->db->bind('active', $arr['active']);
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
    return "Event Correlation Egnine Rule " . $id . " has been deleted";
  }

  public function findRule() {
    $this->db->prepare("SELECT * FROM eventCorrelationEngine ORDER BY id ASC");
    $data = $this->db->resultset();
    return array_values($data);
  }
}
