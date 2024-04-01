<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Monitors;

use App\Domain\Monitors\Monitors;
use App\Domain\Monitors\MonitorsNotFoundException;
use App\Domain\Monitors\MonitorsRepository;
use Database;

class DatabaseMonitorsRepository implements MonitorsRepository {
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  public function createMonitor($arr): array {
    $results = array();
//    return $arr;
    if ( empty($arr['hostId'])) { $arr['hostId'] = ''; }
    if ( empty($arr['hostGroup'])) { $arr['hostGroup'] = ''; }
//    $results += ['result' => $arr];
//    return $results;
    if ( empty($arr['visible'])) { $arr['visible'] = 'no'; }
    $this->db->prepare("INSERT INTO monitoringDevicePoller VALUES( NULL, :checkName, :checkAction, :type, :iteration, :storage, :hostId, :hostGroup, :visible) ON DUPLICATE KEY UPDATE checkName= :checkName1");  // On duplicate do nothing.  User got lazy on names
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->bind('checkName1', $arr['checkName']);
    $this->db->bind('checkAction', $arr['checkAction']);
    $this->db->bind('type', $arr['type']);
    $this->db->bind('iteration', $arr['iteration']);
    $this->db->bind('storage', $arr['storage']);
    $this->db->bind('hostId', $arr['hostId']);
    $this->db->bind('hostGroup', $arr['hostGroup']);
    $this->db->bind('visible', $arr['visible']);
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['firstResult' => 'Create new monitor ' . $arr['checkName'] . ' '. $rString];
    $results += ['firstError' => $errs];

    /*
      Attempt to see if we have a mapping defined for the checks
      If not, then create a basic mapping
    */
    $this->db->prepare("SELECT oid FROM trapEventMap WHERE oid= :checkName");
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->execute();
    $matches = $this->db->rowCount();
    if ( $matches == 0 ) {
      // Even if this fails, create the monitor!  Mapping can be done later in the event of failure, but show it here as well
      $this->db->prepare("INSERT INTO trapEventMap VALUES(:checkName2, :checkName3, 3, '',1 ,NULL,NULL, 3600, NULL");
      $this->db->bind('checkName2', $arr['checkName']);
      $this->db->bind('checkName3', $arr['checkName']);
      $this->db->execute();
      $results += ['secondResult' => 'Created entry for trapEventMap using checkName ' . $arr['checkName']];
      $errs = $this->db->errorInfo();
      $results += ['secondError' => $errs];
    }
    else {
      $results += ['secondResult' => $arr['checkName'] . ' already exists.  No change in trapEventMap table.'];
      $results += ['secondError' => $errs];
    }
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['result' => 'Create new monitor ' . $arr['checkName'] . ' ' . $rString];
    $results += ['errors' => $errs];
    return $results;
  }

  public function monitorAddHost($arr): array {
    $results = array();
    $this->db->prepare("SELECT hostid FROM monitoringDevicePoller WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $hostIdList = json_decode(json_encode($this->db->resultset(),1),true);
    $id = explode(',', $hostIdList[0]['hostid']);
    if ( in_array($arr['hostId'], $id)) {
      return [ 'result' => 'Host id ' . $arr['hostId'] . ' already exists for monitor id ' . $arr['id'] ];
    }
    else {
      $id[] = $arr['hostId'];
    }
    $updatedHostIdList = implode(',', $id);
    $this->db->prepare("UPDATE monitoringDevicePoller SET hostid = :updatedHostId WHERE id= :id2");
    $this->db->bind('updatedHostId', $updatedHostIdList);
    $this->db->bind('id2', $arr['id']);
    $this->db->execute();
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    //$results += ['result' => $data];
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['result' => 'Add hostId ' . $arr['hostId'] . ' to monitor '. $arr['id'] . ' ' . $rString ];
    $results += ['errors' => $errs];
    return $results;
  }

  public function monitorAddHostgroup($arr): array {
    $results = array();
    $this->db->prepare("SELECT hostGroup FROM monitoringDevicePoller WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $hostGroupList = json_decode(json_encode($this->db->resultset(),1),true);
    $hostGroups = explode(',', $hostGroupList[0]['hostGroup']);
    if ( in_array($arr['hostGroup'], $hostGroups)) {
      return ['Host Group ' . $arr['hostGroup'] . ' already exists for id ' . $arr['id'] ];
    }
    else {
      $hostGroups[] = $arr['hostGroup'];
    }
    $updatedHostGroupList = implode(',', $hostGroups);
    $this->db->prepare("UPDATE monitoringDevicePoller SET hostGroup = :updatedHostGroup WHERE id= :id2");
    $this->db->bind('updatedHostGroup', $updatedHostGroupList);
    $this->db->bind('id2', $arr['id']);
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
  }

  public function findMonitors() {
    $results = array();
    $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE type NOT LIKE '%disable%'");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
  }

  public function findMonitorNames() {
    $results = array();
    $this->db->prepare("SELECT id, checkName FROM monitoringDevicePoller");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
    return $data;
  }

  public function findMonitorsDisable() {
     $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE type LIKE '%disable%' ");
    $results = array();
    //$this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE type NOT IN ('get', 'walk', 'snmp', 'nrpe', 'shell', 'housekeeping', 'alive', 'mysql', 'snmptrapd')");  // all "active" things
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
  }

  public function findMonitorsAll() {
    $results = array();
    $this->db->prepare("SELECT * FROM monitoringDevicePoller ");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
  }

  public function findMonitorsByCheckName($arr): array {
    $results = array();
    $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE checkName= :checkName ");
    $this->db->bind('checkName', $arr['checkName']);
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs];
    return $results;
  }

  public function updateMonitor($arr): array {
    $results = array();
    if ( isset ($arr['hostid']) && isset($arr['hostGroup'])) {
      $this->db->prepare("UPDATE monitoringDevicePoller SET checkName= :checkName, checkAction= :checkAction, type= :type, iteration= :iteration, storage= :storage, hostid= :hostid, hostGroup= :hostGroup WHERE id= :id");
      $this->db->bind('hostid', $arr['hostid']);
      $this->db->bind('hostGroup', $arr['hostGroup']);
    }
    elseif ( isset ($arr['hostid'])) {
      $this->db->prepare("UPDATE monitoringDevicePoller SET checkName= :checkName, checkAction= :checkAction, type= :type, iteration= :iteration, storage= :storage, hostid= :hostid WHERE id= :id");
      $this->db->bind('hostid', $arr['hostid']);
    }
    elseif ( isset ($arr['hostGroup'])) {
      $this->db->prepare("UPDATE monitoringDevicePoller SET checkName= :checkName, checkAction= :checkAction, type= :type, iteration= :iteration, storage= :storage, hostGroup= :hostGroup WHERE id= :id");
      $this->db->bind('hostGroup', $arr['hostGroup']);
    }
    else {
      $this->db->prepare("UPDATE monitoringDevicePoller SET checkName= :checkName, checkAction= :checkAction, type= :type, iteration= :iteration, storage= :storage WHERE id= :id");
    }
    $this->db->bind('id', $arr['id']);
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->bind('checkAction', $arr['checkAction']);
    $this->db->bind('type', $arr['type']);
    $this->db->bind('iteration', $arr['iteration']);
    $this->db->bind('storage', $arr['storage']);
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data ];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function deleteMonitor($id) {
    $results = array();
    $this->db->prepare("DELETE FROM monitoringDevicePoller WHERE id= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    $errs = $this->db->errorInfo();
    $data = ['Deleted monitor id: ' . $id];
    $results = ['result' => $data ];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function monitorDeleteHost($arr): array {
    $results = array();
    $this->db->prepare("SELECT hostid FROM monitoringDevicePoller WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $hostIdList = json_decode(json_encode($this->db->resultset(),1),true);
    if ( ! empty($hostIdList[0]['hostid'])) {
      $id = explode(',', $hostIdList[0]['hostid']);
    }
    else {
      // No hosts were returned, so simply say "ok"
      $errs = $this->db->errorInfo();
      $results += ['result' => 'Remove hostId ' . $arr['hostId'] . ' from monitor ' . $arr['id'] . ' could not be done.  No hosts are set for this monitor' ];
      $results += ['errors' => $errs ];
      return $results;
    }
    $toRemove = array($arr['hostId']);
    $updatedHostIdList = array_diff($id, $toRemove);
    $updatedHostIdList = array_values(array_filter($updatedHostIdList));
    $updatedHostIdList =implode(',', $updatedHostIdList);
    $this->db->prepare("UPDATE monitoringDevicePoller SET hostid = :updatedHostId WHERE id= :id2");
    $this->db->bind('updatedHostId', $updatedHostIdList);
    $this->db->bind('id2', $arr['id']);
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['result' => 'Remove hostId ' . $arr['hostId'] . ' from monitor ' . $arr['id'] . ' ' . $rString ];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function monitorDeleteHostgroup($arr): array {
    $results = array();
    $this->db->prepare("SELECT hostGroup FROM monitoringDevicePoller WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $hostGroupList = json_decode(json_encode($this->db->resultset(),1),true);
    $hostGroupList[0]['hostGroup'] = preg_replace('/ /', '', $hostGroupList[0]['hostGroup']);
    $hostGroup = explode(',', $hostGroupList[0]['hostGroup']);
    $toRemove = [$arr['hostGroup']];
    $updatedHostGroupList = array_diff($hostGroup, $toRemove);
    $updatedHostGroupList = array_values(array_filter($updatedHostGroupList));
    $updatedHostGroupList = implode(',', $updatedHostGroupList);
    $this->db->prepare("UPDATE monitoringDevicePoller SET hostGroup = :updatedHostGroup WHERE id= :id2");
    $this->db->bind('updatedHostGroup', $updatedHostGroupList);
    $this->db->bind('id2', $arr['id']);
    $data = $this->db->execute();
    $errs = $this->db->errorInfo();
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['result' => 'Remove hostGroup name ' . $arr['hostGroup'] . ' from monitor '. $arr['id'] . ' ' . $rString ];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function findMonitorIteration() {
    $results = array();
    $this->db->prepare("SELECT DISTINCT(iteration) FROM monitoringDevicePoller WHERE storage NOT IN ('housekeeping', 'snmptrapd', 'mysql', 'test', 'disable')");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function findMonitorstorage() {
    $results = array();
    $this->db->prepare("select distinct storage from monitoringDevicePoller WHERE storage NOT IN ('housekeeping', 'snmptrapd', 'mysql', 'test', 'disable')");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function findMonitorType() {
    $results = array();
    $this->db->prepare("select distinct type from monitoringDevicePoller WHERE storage NOT IN ('housekeeping', 'snmptrapd', 'mysql', 'test', 'disable')");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function findDeviceId() {
    $results = array();
    $this->db->prepare("SELECT id, hostname, address FROM Device");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results ;
  }

  public function findHostGroup() {
    $results = array();
    $this->db->prepare("SELECT id, devicegroupName FROM DeviceGroup");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results ;
  }
  public function findMonitorsByHostId($arr):array {
    $results = array();
    $cleanDeviceGroup = '';
    if ( isset($arr['id'])) {
      $query = "SELECT DISTINCT(devicegroupName) FROM DeviceGroup WHERE hostname LIKE '%\"" . $arr['id'] . "\"%'";
      $this->db->prepare("$query");
      $data = $this->db->resultset();
    }
    else {
      $cleanDeviceGroup = '';
    }
    foreach ($data as $dat) {
      foreach ($dat as $k => $v) {
        if ( ! $cleanDeviceGroup == '') {
          $cleanDeviceGroup .= ', "' . $v . '"';
        }
        else {
          $cleanDeviceGroup .= '"' . $v . '"';
        }
      }
    }
    /*
      We now have a pretty devicegroup list for looking for our monitors now
      Note that we are not binding stuff, as the in clause gets grouchy, so we have
      to do this the old-school way
    */
    if ( empty($data)) {
      $query2="SELECT id, checkName, type, iteration, storage, hostId, hostGroup FROM monitoringDevicePoller WHERE find_in_set(" . $arr['id'] .", hostId) GROUP BY id";
    }
    else {
      $group = '';
      $groupList = explode(',', $cleanDeviceGroup);
      foreach ($groupList as $singleGroup) {
        $singleGroup = preg_replace('/"/', '', $singleGroup);
        $group .= ' OR INSTR(hostGroup, "' . $singleGroup . '")';
      }
      // $query2="SELECT id, checkName, type, iteration, storage, hostId, hostGroup FROM monitoringDevicePoller WHERE find_in_set(" . $arr['id'] .", hostId) OR INSTR(hostGroup, " . $cleanDeviceGroup . ") GROUP BY id"; // bad, cannot take n+1 INSTR values
      $query2='SELECT id, checkName, type, iteration, storage, hostId, hostGroup FROM monitoringDevicePoller WHERE find_in_set(' . $arr['id'] .', hostId) ' . $group . ' GROUP BY id';
    }
    $this->db->prepare("$query2");
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

} // end class
