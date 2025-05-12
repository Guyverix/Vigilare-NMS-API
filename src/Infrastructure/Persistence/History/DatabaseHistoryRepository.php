<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\History;

use App\Domain\History\History;
use App\Domain\History\HistoryNotFoundException;
use App\Domain\History\HistoryRepository;

require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseHistoryRepository implements HistoryRepository {
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  public function findAll(): array {
    // We are going to hard limit this so it does not crash on large history sizes
    $this->db->prepare("SELECT h.*, d.id FROM history h LEFT JOIN Device d ON h.device=d.hostname ORDER BY h.startEvent DESC LIMIT 4000");
    $data = $this->db->resultset();
    return array_values($data);
  }

  public function findLimit(int $limit): array {
    $this->db->prepare("SELECT h.*, d.id FROM history h LEFT JOIN Device d ON h.device=d.hostname ORDER BY h.startEvent DESC LIMIT $limit");
    $data = $this->db->resultset();
    return array_values($data);
  }

  public function findHistoryOfId(string $evid): array {
    $evid = str_replace('/','',$evid);
    if (!isset($evid)) {
      throw new HistoryNotFoundException();
    }
    $this->db->prepare("SELECT * FROM history WHERE evid = :evid");
    $this->db->bind('evid', $evid);
    $data = $this->db->resultset();
    return $data;
  }

  public function findSingleHistoryOfId(string $evid): array {
    $evid = str_replace('/','',$evid);
    if (!isset($evid)) {
      throw new HistoryNotFoundException();
    }
    $this->db->prepare("SELECT * FROM history WHERE evid = :evid");
    $this->db->bind('evid', $evid);
    $data = $this->db->resultset();
    return $data;
  }

  public function findStateChangeBeforeOfHistory(string $stateChange):array {
    if (!isset($stateChange)) {
      throw new HistoryNotFoundException();
    }
     $this->db->prepare("SELECT * FROM history WHERE stateChange <= :stateChange");
     $this->db->bind('stateChange', $stateChange);
     $data = $this->db->resultset();
     return $data;
  }

  public function findStateChangeAfterOfHistory(string $stateChange):array {
    if (!isset($stateChange)) {
      throw new HistoryNotFoundException();
    }
    $this->db->prepare("SELECT * FROM history WHERE stateChange >= :stateChange");
    $this->db->bind('stateChange', $stateChange);
    $data = $this->db->resultset();
    return $data;
  }


  // Odd PDO behavior with bindings.  Try to only bind filter if possible
  public function findColumnDirectionOfHistory(string $column, string $direction, string $filter):array {
    $column=preg_replace('/"/','',$column);
    if ( $direction == "like" ) {
      $this->db->prepare("SELECT * FROM history WHERE $column LIKE :filter ");
      $filter = '%'. $filter . '%';
      $this->db->bind('filter', $filter);
    }
    else {
      if ( $direction == "before" ) { $filter2='<='; }
      if ( $direction == "after" ) { $filter2='>='; }
      if ( $direction == "equal" ) { $filter2='='; }
      $this->db->prepare("SELECT * FROM history WHERE $column $filter2 :filter");
      $this->db->bind('filter', $filter);
    }
    $data = $this->db->resultset();
    return $data;
  }

  public function findTableNames(string $table):array {
     $this->db->prepare("DESCRIBE $table");
     $data = $this->db->resultset();
     $data = array($data);
     return $data;
   }

  public function countHistoryHistoryHostsSeen() {
     $this->db->prepare("SELECT COUNT(DISTINCT(device)) AS count FROM history");
     $data = $this->db->resultset();
     return $data;
   }

  public function historyEventCount() {
     $this->db->prepare("SELECT COUNT(*) AS count FROM history");
     $data = $this->db->resultset();
     return $data;
   }

  public function countHistoryAllHostsSeen() {
     $this->db->prepare("SELECT COUNT(DISTINCT(event.device)) as count FROM (SELECT device AS device FROM event UNION ALL SELECT device FROM history )event");
     $data = $this->db->resultset();
     return $data;
   }

  // https://www.tutorialspoint.com/how-to-get-the-count-of-each-distinct-value-in-a-column-in-mysql
  public function countHistorySingleHost($hostname) {
    $this->db->prepare("select eventSeverity, count(1) as count from history WHERE device= :hostname group by eventSeverity");
    $this->db->bind('hostname', $hostname);
    $data = $this->db->resultset();
    return $data;
  }

  // https://www.tutorialspoint.com/how-to-get-the-count-of-each-distinct-value-in-a-column-in-mysql
  public function countHistoryGroupOfHosts($hostList) {
    $this->db->prepare("select eventSeverity, count(1) as count from history WHERE device in :hostList group by eventSeverity");
    $this->db->bind('hostList', $hostList);
    $data = $this->db->resultset();
    return $data;
  }
}
