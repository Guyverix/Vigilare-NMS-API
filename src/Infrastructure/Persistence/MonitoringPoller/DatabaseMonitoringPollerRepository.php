<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\MonitoringPoller;

use App\Domain\MonitoringPoller\MonitoringPoller;
use App\Domain\MonitoringPoller\MonitoringPollerNotFoundException;
use App\Domain\MonitoringPoller\MonitoringPollerRepository;
use Database;

class DatabaseMonitoringPollerRepository implements MonitoringPollerRepository {

  public $db = "";

  public function __construct() {
    $this->db = new Database();
  }

  public function FindMonitoringHostname($arr): array {
    // Dont bother with a clean list using bind, it does odd crap with commas and quotes, etc.
    // $this->db->prepare("SELECT * FROM Device WHERE id IN ( :idList )");
    // $this->db->quote($arr['idList']);
    // $this->db->bind('idList', (string)$arr['idList']);
    // $this->db->prepare("SELECT * FROM Device WHERE id IN (" . $arr['idList'] . ")");
    // Debugging the damn simple query to see WTF is happening
    // $data2 = json_decode(json_encode($this->db,1),true);
    // $arr=array_merge($arr, $data);
    // $arr = array_merge($arr, $data2);
    // $arr = array_merge($arr, json_decode(json_encode($this->db,1),True));
    $this->db->prepare("SELECT d.hostname,d.address,d.productionState,d.isAlive,p.Properties FROM Device d LEFT JOIN DeviceProperties p on p.DeviceId=d.id  WHERE d.id IN (" . $arr['idList'] . ")");
    $data = $this->db->resultset();
    return $data;
  }

  public function FindMonitoringId($arr):array {
    $this->db->prepare("SELECT hostname from  DeviceGroup WHERE devicegroupName= :devicegroup");
    $this->db->bind('devicegroup', $arr['hostgroup']);
    $data = $this->db->resultset();
    return $data;
  }

  public function FindMonitoringPoller($arr): array {
    if ( $arr['action'] == "snmp" ) {
      $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE iteration= :cycle AND (type='get' OR type='walk') AND (hostid !='' OR hostGroup !='')");
      $this->db->bind('cycle', $arr['cycle']);
    }
    else {
      $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE iteration= :cycle AND type= :action AND (hostid !='' OR hostGroup !='')");
      $this->db->bind('action', $arr['action']);
      $this->db->bind('cycle', $arr['cycle']);
    }
    $data=$this->db->resultset();
    return $data;
  }

  public function FindMonitoringPollerAll($arr): array {
    $this->db->prepare("SELECT * FROM monitoringDevicePoller");
    $data=$this->db->resultset();
    return $data;
  }

  public function FindMonitoringPollerDisable($arr): array { 
    $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE type NOT IN ('walk','get','ping','nrpe','housekeeping','shell','snmptrapd','mysql')");
    $data=$this->db->resultset();
    return $data;
  }

  public function housekeeping($arr): array {
    if ( isset($arr['query'])) { // future
    }
    $this->db->prepare("SELECT * FROM heartbeat");
    $data=$this->db->resultset();
    return $data;
  }

  public function saveHeartBeat($arr): array {
    $arr['pollerCycle'] = "iteration_" . $arr['pollerCycle'];
    $this->db->prepare("INSERT INTO heartbeat VALUES( :poller, :cycle, NOW(), :pid ) ON DUPLICATE KEY UPDATE lastTime= NOW(), pid= :pid");
    $this->db->bind('poller', $arr['pollerName']);
    $this->db->bind('cycle',  $arr['pollerCycle']);
    $this->db->bind('pid',    $arr['pollerPid']);
    $data=$this->db->resultset();
    return $data;
  }

  public function savePerformance($arr): array {
    if (empty ($arr['value'])) {
      return ['error'];
    }
    $this->db->prepare("INSERT INTO performance VALUES(:hostname, :checkName, NOW(), :value) ON DUPLICATE KEY UPDATE date= NOW(), value= :value");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->bind('value', $arr['value']);
    $data=$this->db->resultset();
    return $data;
  }

  public function deletePerformance($arr): array {
    if (empty ($arr['days'])) {
      return ['error'];
    }
    $minusDays = strtotime('-' . $arr['days'] . ' days', time());
    $cleanDays =  gmdate('Y-m-d H:i:s', $minusDays);
    $this->db->prepare("SELECT COUNT(*) AS count FROM performance WHERE date <= :daysBack");
    $this->db->bind('daysBack', $cleanDays);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1),true);
    $cleanup = $data[0]['count'];
    if ( empty($cleanup)) { $cleanup = 0; }
    $this->db->prepare("DELETE FROM performance WHERE date <= :daysBack1");
    $this->db->bind('daysBack1', $cleanDays);
    $this->db->execute();
    return ['Removed ' . $cleanup . ' performance metrics older than date ' . $cleanDays];
  }

  public function saveAlive($arr): array {
    $this->db->prepare("UPDATE Device SET isAlive= :isAlive WHERE hostname= :hostname AND address= :address");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('address', $arr['address']);
    $this->db->bind('isAlive', $arr['isAlive']);
    $data=$this->db->resultset();
    return $data;
  }

  // Return all monitors for a given ID.  This includes looking in DeviceGroup for ID as well.
  public function FindMonitorsById($arr): array {
    // Bindings get goofy with quotes, do the prepare manually
    $query='SELECT distinct(devicegroupName) FROM DeviceGroup WHERE hostname LIKE ' . '\'%"' . $arr['id'] . '"%\'' . '';
    $this->db->prepare($query);  // necessary this way due to it being JSON
    $devicegroupNames=$this->db->resultset();
    $append = '';
    $devicegroupNames=json_decode(json_encode($devicegroupNames,1),true);
    if ( count($devicegroupNames) !== 0) {
      // We know we have a match for id in here and at least 1 deviceGroup defined
      foreach ( $devicegroupNames as $deviceGroup) {
        foreach ( $deviceGroup as $k => $v ) {
          $groupNames=ltrim(rtrim($v));
          $append .= " OR hostGroup LIKE " . '"%' . $groupNames . '%"' . '';  // Strings in a DB can be a PITA
        }
      }
    }
    // hostid is a CSV, so we need 3 LIKES to make sure we find our ID in the list
//    $query='SELECT checkName, type, storage FROM monitoringDevicePoller WHERE hostid LIKE \'' . $arr['id'] . ',%\' OR hostid LIKE \'%,' . $arr['id'] . ',%\' OR hostid LIKE \'%,' .$arr['id'] . '\''; 
    $query='SELECT checkName, type, storage FROM monitoringDevicePoller WHERE hostid LIKE \'' . $arr['id'] . '\' OR hostid LIKE \'%,' . $arr['id'] . ',%\' OR hostid LIKE \'%,' .$arr['id'] . '\' OR hostid LIKE \'' . $arr['id'] . ',%\''; 
    $this->db->prepare($query . $append);
    $checkNames=$this->db->resultset();
    return $checkNames;
  }
} // End CLASS
