<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event;

use App\Domain\Event\Event;
use App\Domain\Event\EventNotFoundException;
use App\Domain\Event\EventRepository;

// require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseEventRepository implements EventRepository {
  public $db = "";

  public function __construct() {
    $this->db = new Database();
  }

  public function findAll(): array {
    $this->db->prepare("SELECT e.*, d.id FROM event e  LEFT JOIN Device d ON e.device=d.hostname ORDER BY startEvent DESC");
    $data = $this->db->resultset();
    return array_values($data);
  }

  public function findEventOfId(string $evid): array {
    $evid = str_replace('/','',$evid);
    if (!isset($evid)) {
      throw new EventNotFoundException();
    }
    $this->db->prepare("SELECT * FROM event WHERE evid = :evid");
    $this->db->bind('evid', $evid);
    $data = $this->db->resultset();
    return $data;
  }

  public function findSingleEventOfId(string $evid): array {
    $evid = str_replace('/','',$evid);
    if (!isset($evid)) {
      throw new EventNotFoundException();
    }
    $this->db->prepare("SELECT * FROM event WHERE evid = :evid");
    $this->db->bind('evid', $evid);
    $data = $this->db->resultset();
    return $data;
  }

  public function findStateChangeBeforeOfEvent(string $stateChange):array {
    if (!isset($stateChange)) {
      throw new EventNotFoundException();
    }
     $this->db->prepare("SELECT * FROM event WHERE stateChange <= :stateChange");
     $this->db->bind('stateChange', $stateChange);
     $data = $this->db->resultset();
     return $data;
  }

  public function findStateChangeAfterOfEvent(string $stateChange):array {
    if (!isset($stateChange)) {
      throw new EventNotFoundException();
    }
    $this->db->prepare("SELECT * FROM event WHERE stateChange >= :stateChange");
    $this->db->bind('stateChange', $stateChange);
    $data = $this->db->resultset();
    return $data;
  }


  // Odd PDO behavior with bindings.  Try to only bind filter if possible
  public function findSortedEvents(string $column, string $direction):array {
    $column=preg_replace('/"/','',$column);
    //    $this->db->prepare("SELECT eventSeverity FROM event ORDER BY :column :direction");
    //    $this->db->bind('column', $column);
    //    $this->db->bind('direction', $direction);
    $this->db->prepare("SELECT * FROM event e LEFT JOIN Device d ON d.hostname = e.device ORDER BY e.$column $direction");
    $data = $this->db->resultset();
    return $data;
  }

  // Odd PDO behavior with bindings.  Try to only bind filter if possible
  public function findColumnDirectionOfEvent(string $column, string $direction, string $filter):array {
    $column=preg_replace('/"/','',$column);
    if ( $direction == "like" ) {
      $this->db->prepare("SELECT * FROM event WHERE $column LIKE :filter ");
      $filter = '%'. $filter . '%';
      $this->db->bind('filter', $filter);
    }
    else {
      if ( $direction == "before" ) { $filter2='<='; }
      if ( $direction == "after" ) { $filter2='>='; }
      if ( $direction == "equal" ) { $filter2='='; }
      $this->db->prepare("SELECT * FROM event WHERE $column $filter2 :filter");
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

  public function countEventEventHostsSeen() {
     $this->db->prepare("SELECT COUNT(DISTINCT(device)) AS count FROM event");
     $data = $this->db->resultset();
     return $data;
   }

  public function activeEventCount() {
     $this->db->prepare("SELECT COUNT(*) AS count FROM event");
     $data = $this->db->resultset();
     return $data;
   }

  public function activeEventCountList() {
     $this->db->prepare("SELECT eventSeverity AS severity, count(eventSeverity) AS count FROM event GROUP BY eventSeverity;");
     $data = $this->db->resultset();
     return $data;
   }

  public function historyEventCount() {
     $this->db->prepare("SELECT COUNT(*) AS count FROM history");
     $data = $this->db->resultset();
     return $data;
   }

  public function countEventAllHostsSeen() {
     $this->db->prepare("SELECT COUNT(DISTINCT(event.device)) AS count FROM (SELECT device AS device FROM event UNION ALL SELECT device FROM history )event");
     $data = $this->db->resultset();
     return $data;
   }

  // https://www.tutorialspoint.com/how-to-get-the-count-of-each-distinct-value-in-a-column-in-mysql
  public function countEventSingleHost($hostname) {
    $this->db->prepare("select eventSeverity, count(1) AS count from event WHERE device= :hostname group by eventSeverity");
    $this->db->bind('hostname', $hostname);
    $data = $this->db->resultset();
    return $data;
  }

  // https://www.tutorialspoint.com/how-to-get-the-count-of-each-distinct-value-in-a-column-in-mysql
  public function countEventGroupOfHosts($hostList) {
    $this->db->prepare("select eventSeverity, count(1) AS count from event WHERE device in :hostList group by eventSeverity");
    $this->db->bind('hostList', $hostList);
    $data = $this->db->resultset();
    return $data;
  }

  public function monitorList() {
    $this->db->prepare("SELECT device, eventName FROM event");
    $data = $this->db->resultset();
    return $data;
  }

  public function ageOut() {
    $this->db->prepare("SELECT evid FROM event WHERE stateChange <= NOW() - INTERVAL eventAgeOut SECOND");
    $data = $this->db->resultset();
    return $data;
  }

  public function findActiveEventByHostname($hostname) {
    $this->db->prepare("SELECT * from event WHERE device= :hostname");
    $this->db->bind("hostname", $hostname);
    $data = $this->db->resultset();
    return $data;
  }

  public function findClosedEventByHostname($hostname) {
    $this->db->prepare("SELECT * from event WHERE device= :hostname");
    $this->db->bind("hostname", $hostname);
    $data = $this->db->resultset();
    return $data;
  }

  // CONCAT does not seem to work as expected inside the prepare, so do external $query
  public function moveToHistory($id, $reason) {
    $arr = array('id' => $id, 'reason' => $reason);
    $this->db->prepare("INSERT INTO history SELECT e.* FROM event e WHERE e.evid= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    $query="UPDATE history SET endEvent=now(), eventDetails=CONCAT(eventDetails, ',[ moveToHistory reason: " . $reason . "]') WHERE evid = '" . $id . "'";
    $this->db->prepare($query);
    $this->db->execute();
    $this->db->prepare("DELETE FROM event WHERE evid=  :id3");
    $this->db->bind('id3', $id);
    $this->db->execute();
    $errs = $this->db->errorInfo();
    $arr += ['errors' => $errs];
    return $arr;
  }

  // CONCAT does not seem to work as expected inside the prepare, so do external $query
  public function moveFromHistory($id, $reason) {
    $arr = array('id' => $id, 'reason' => $reason);
    $this->db->prepare("INSERT INTO event SELECT h.* FROM history h WHERE h.evid= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    $query = "UPDATE event SET endEvent='0000-00-00 00:00:00', eventDetails=CONCAT(eventDetails,',[ moveToEvent reason: " . $reason . "]') WHERE evid = '" . $id . "'";
    $this->db->prepare($query);
    $this->db->execute();
    $this->db->prepare("DELETE FROM history WHERE evid=  :id3");
    $this->db->bind('id3', $id);
    $this->db->execute();
    $errs = $this->db->errorInfo();
    $arr += ['errors' => $errs];
    return $arr;
  }
  public function findActiveEventByDeviceId($id) {
    $this->db->prepare("SELECT e.* FROM event e LEFT JOIN Device d ON d.hostname = e.device WHERE d.id= :id");
    $this->db->bind('id', $id);
    $data = $this->db->resultset();
    return $data;
  }

  // Yes we are limiting to 200 events for device
  public function findHistoryEventByDeviceId($id) {
    $this->db->prepare("SELECT h.* FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id ORDER BY h.endEvent DESC LIMIT 200");
    $this->db->bind('id', $id);
    $data = $this->db->resultset();
    return $data;
  }

  public function findHistoryTime($arr) {
    if ( empty($arr['startEvent'])) {
      $minus30 = strtotime('-30 days', time());
      $arr['startEvent'] =  gmdate('Y-m-d H:i:s', $minus30);
    }
    $this->db->prepare("SELECT SUM(downtime) AS totalDowntime FROM (SELECT TIMESTAMPDIFF(minute, startEvent, endEvent) AS downtime FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id AND h.startEvent >= :startEvent) t1");
    $this->db->bind('id', $arr['id']);
    $this->db->bind('startEvent', $arr['startEvent']);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1), true);  // convert from obj to array
    if ( is_null( $data[0]['totalDowntime'])) {
     $data[0]['totalDowntime'] = 0;
    }
    return $data;
  }

  public function findEventTime($arr) {
    if ( empty($arr['startEvent'])) {
      $minus30 = strtotime('-30 days', time());
      $arr['startEvent'] =  gmdate('Y-m-d H:i:s', $minus30);
    }
    $this->db->prepare("SELECT SUM(downtime) AS totalDowntime FROM (SELECT TIMESTAMPDIFF(minute, startEvent, now()) AS downtime FROM event e LEFT JOIN Device d ON d.hostname = e.device WHERE d.id= :id AND e.startEvent >= :startEvent) t1");
    $this->db->bind('id', $arr['id']);
    $this->db->bind('startEvent', $arr['startEvent']);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1), true);  // convert from obj to array
    if ( is_null( $data[0]['totalDowntime'])) {
     $data[0]['totalDowntime'] = 0;
    }
    return $data;
  }

  // Calc downtime by ping down minutes looking at now and history
  public function findAliveTime($arr) {
    if ( empty($arr['startEvent'])) {
      $minus30 = strtotime('-30 days', time());
      $arr['startEvent'] =  gmdate('Y-m-d H:i:s', $minus30);
    }
    // Query 1 from event
    $this->db->prepare("SELECT SUM(downtime) AS totalDowntime FROM (SELECT TIMESTAMPDIFF(minute, startEvent, now()) AS downtime FROM event e LEFT JOIN Device d ON d.hostname = e.device WHERE d.id= :id AND e.startEvent >= :startEvent AND eventName='ping') t1");
    $this->db->bind('id', $arr['id']);
    $this->db->bind('startEvent', $arr['startEvent']);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1), true);  // convert from obj to array
    if ( is_null( $data[0]['totalDowntime'])) {
     $data[0]['totalDowntime'] = 0;
    }
    // Query logic for timeband history
    $this->db->prepare("SELECT count(*) AS count FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id3 AND h.endEvent >= :startEvent3 AND eventName='ping'");
    $this->db->bind('id3', $arr['id']);
    $this->db->bind('startEvent3', $arr['startEvent']);
    $data3 = $this->db->resultset();
    $data3 = json_decode(json_encode($data3,1), true);  // convert from obj to array
    if ( is_null( $data3[0]['count'])) {
     $count = 0;
    }
    else {
     $count = $data3[0]['count'];
    }
    // Band check
    $this->db->prepare("SELECT count(*) AS count FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id4 AND (h.endEvent >= :startEvent4 AND h.startEvent >= :startEvent5 AND eventName='ping')");
    $this->db->bind('id4', $arr['id']);
    $this->db->bind('startEvent4', $arr['startEvent']);
    $this->db->bind('startEvent5', $arr['startEvent']);
    $data4 = $this->db->resultset();
    $data4 = json_decode(json_encode($data4,1), true);  // convert from obj to array
    if ( is_null( $data4[0]['count'])) {
     $countBand = 0;
    }
    else {
     $countBand = $data4[0]['count'];
    }
    if ( $count > 0 && $countBand == 0) {  // We have X events, with none within band so we set start of event as our band limit
      $this->db->prepare("SELECT SUM(downtime) AS totalDowntime FROM (SELECT TIMESTAMPDIFF(minute, :startEvent7, endEvent) AS downtime FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id6 AND h.endEvent >= :startEvent6 AND eventName='ping') t1");
      $this->db->bind('id6', $arr['id']);
      $this->db->bind('startEvent6', $arr['startEvent']);
      $this->db->bind('startEvent7', $arr['startEvent']);
      $data2 = $this->db->resultset();
      $data2 = json_decode(json_encode($data2,1), true);  // convert from obj to array
      if ( is_null( $data2[0]['totalDowntime'])) {
       $data2[0]['totalDowntime'] = 0;
      }
    }
    else {  // We are within band, so a generic check is all we need
      // Query 2 from history (basic)
      $this->db->prepare("SELECT SUM(downtime) AS totalDowntime FROM (SELECT TIMESTAMPDIFF(minute, startEvent, endEvent) AS downtime FROM history h LEFT JOIN Device d ON d.hostname = h.device WHERE d.id= :id2 AND h.startEvent >= :startEvent2 AND eventName='ping') t1");
      $this->db->bind('id2', $arr['id']);
      $this->db->bind('startEvent2', $arr['startEvent']);
      $data2 = $this->db->resultset();
      $data2 = json_decode(json_encode($data2,1), true);  // convert from obj to array
      if ( is_null( $data2[0]['totalDowntime'])) {
       $data2[0]['totalDowntime'] = 0;
      }
      // Add our two values together for number of mins ping has been alarming
    }
    $results = $data[0]['totalDowntime'] + $data2[0]['totalDowntime'];
    $data3[0]['totalDowntime'] = $results;
    return $data3;
  }
}
