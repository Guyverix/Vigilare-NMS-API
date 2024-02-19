<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Device;

use App\Domain\Device\Device;
use App\Domain\Device\DeviceNotFoundException;
use App\Domain\Device\DeviceRepository;
use Database;

class DatabaseDeviceRepository implements DeviceRepository {
  private $oid;
  private $display_name;
  private $severity;
  private $pre_processing;
  private $type;
  private $parent_of;
  private $child_of;
  private $age_out;
  private $post_processing;
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  public function findAllHost() {
    $this->db->prepare("SELECT * FROM Device");
    $data = $this->db->resultset();
    return $data;
  }

  public function findDeviceGroup() {
    $this->db->prepare("SELECT devicegroupName FROM DeviceGroup");
    $data = $this->db->resultset();
    return $data;
  }

  public function findDeviceGroupMonitors($array) {
    // The double-quotes mess up the binding, do it by hand in this case
    $query = 'SELECT checkName FROM monitoringDevicePoller WHERE INSTR(hostGroup, "' . $array['deviceGroupMonitors'] . '")';
    $this->db->prepare($query);
    $data = $this->db->resultset();
    return $data;
  }

  public function findDeviceInDeviceGroup($array) {
    $this->db->prepare("SELECT devicegroupName FROM DeviceGroup WHERE INSTR(hostname, :hostname)");
    $this->db->bind('hostname', '"' . $array['deviceInDeviceGroup']. '"');
    $data = $this->db->resultset();
    return $data;
  }

  public function createDeviceGroup($array) {
    if ( empty($array['hostname']) || ! isset($array['hostname'])) { $array['hostname'] = ''; }
    $this->db->prepare("INSERT INTO DeviceGroup VALUES('', :devicegroupName, :hostname) ON DUPLICATE KEY UPDATE devicegroupName= :devicegroupName1");
    $this->db->bind('devicegroupName', $array['devicegroupName']);
    $this->db->bind('devicegroupName1', $array['devicegroupName']);
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->execute();
    return $data;
  }

  public function updateDeviceGroup($array) {
    $this->db->prepare("SELECT hostname FROM DeviceGroup WHERE devicegroupName = :deviceGroup");
    $this->db->bind('deviceGroup', $array['deviceGroup']);
    $hostnameData1 = $this->db->resultset();
    $hostnameData1 = json_decode(json_encode($hostnameData1,1),true);
    $hostnameData = $hostnameData1[0];
    $hostnameData['hostname'] = json_decode($hostnameData['hostname'],true);
    if (empty($hostnameData['hostname'])) { $hostnameData['hostname'] = array(); }
    if ($array['change'] == 'remove' && isset($array['hostname'])) {
      $removed = array_search($array['hostname'], $hostnameData['hostname']);
      if ($removed !== false) {    // If the hostname IS in the array, remove it
        unset($hostnameData['hostname'][$removed]);
      }
      $this->db->prepare("UPDATE DeviceGroup SET hostname= :hostname WHERE devicegroupName = :deviceGroup2");
      $this->db->bind('hostname' , json_encode($hostnameData['hostname'],1));
      $this->db->bind('deviceGroup2', $array['deviceGroup']);
      $hostnameData = $this->db->execute();
      return [$array['change'] . " run against " . $array['hostname'] . " for deviceGroup " . $array['deviceGroup']];
    }
    elseif ( $array['change'] == 'add' && isset($array['hostname'])) {
      if (! in_array($array['hostname'], $hostnameData['hostname'])) {  // if the hostname is not in the array already add it now
        $hostnameData['hostname'][] = $array['hostname'];
      }
      $this->db->prepare("UPDATE DeviceGroup SET hostname= :hostname WHERE devicegroupName = :deviceGroup3");
      $this->db->bind('hostname' , json_encode($hostnameData['hostname'],1));
//      $this->db->bind('hostname' , $hostnameData['hostname']);
      $this->db->bind('deviceGroup3', $array['deviceGroup']);
      $hostnameData = $this->db->execute();
      return [$array['change'] . " run against " . $array['hostname'] . " for deviceGroup " . $array['deviceGroup']];
    }
    else {
      return ["Error.  Change type was not sent or was wrong."];
    }
    // We should never get here!
    $this->db->prepare("SELECT * FROM DeviceGroup WHERE devicegroupName = :deviceGroup");
    $this->db->bind('deviceGroup', $array['deviceGroup']);
    $data = $this->db->resultset();
    return $data;
  }

  public function deleteDeviceGroup($array) {
    $this->db->prepare("DELETE FROM DeviceGroup WHERE devicegroupName = :deviceGroup");
    $this->db->bind('deviceGroup', $array['deviceGroup']);
    $data = $this->db->execute();
    return ["If deviceGroup existed, it was removed"];
  }

  public function findHost($array) {
    $this->db->prepare("SELECT * FROM Device WHERE hostname = :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findId($array) {
    $this->db->prepare("SELECT * FROM Device WHERE id = :id");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findAddress($array) {
    $this->db->prepare("SELECT * FROM Device WHERE address = :address");
    $this->db->bind('address', $array['address']);
    $data = $this->db->resultset();
    return $data;
  }

  public function createHost($array) {
    $this->db->prepare("INSERT INTO Device VALUES(NULL, :hostname, :address, NOW(), :productionState, 'unknown' )");
    $this->db->bind(':hostname', $array['hostname']);
    $this->db->bind(':address', $array['address']);
    $this->db->bind(':productionState', $array['productionState']);
    $this->db->execute();
    $this->db->prepare("SELECT * FROM Device WHERE hostname= :hostname AND address= :address AND productionState= :productionState");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('productionState', $array['productionState']);
    $data1 = $this->db->resultset();
    return $data1;
  }

  public function updateHost($array) {
    // this also calls update events after changes here.
    $this->db->prepare("UPDATE Device SET hostname= :hostname, address= :address, productionState= :productionState WHERE id= :id");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('productionState', $array['productionState']);
    $this->db->bind('id', $array['id']);
    $this->db->resultset();
    $this->db->prepare("SELECT * FROM Device WHERE id= :id");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    $row = $this->db->rowCount();
    if ( $row > 0 ) {
        return $data;
    }
    else {
      return null;
    }
  }

  public function updateEvents($array) {
    if (empty($array)) {
      return null;
    }
    $this->db->prepare("UPDATE event SET device=:hostname WHERE device= :address");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->execute();
    $this->db->prepare("SELECT count(*) AS 'active events' FROM event WHERE device= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function deleteHost($array) {
    $this->db->prepare("DELETE FROM Device WHERE id= :id");
    $this->db->bind('id', $array['id']);
    $this->db->execute();
    return "id match for Host deletion";
  }

  public function deleteHostId($array) {
    $this->db->prepare("DELETE FROM Device WHERE id= :id");
    $this->db->bind('id', $array['id']);
    $this->db->execute();
    return "Deleted Device id " . $array['id'] . " Host deletion complete.";
  }

  public function findAttribute($array) {
    $this->db->prepare("SELECT component, name, value FROM hostAttribute WHERE hostname= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findPerformance($array) {
    if ( isset($array['id'])) {
      $this->db->prepare("SELECT p.checkName, p.date, p.value FROM performance p LEFT JOIN  Device d on d.hostname=p.hostname WHERE d.id= :id");
      $this->db->bind('id', $array['id']);
    }
    else {
      $this->db->prepare("SELECT checkName, date, value FROM performance WHERE hostname= :hostname");
      $this->db->bind('hostname', $array['hostname']);
    }
    $data = $this->db->resultset();
    return $data;
  }

  public function propertiesHost($array) {
    $this->db->prepare("SELECT d.*, p.properties FROM Device d LEFT JOIN DeviceProperties p on p.DeviceId=d.id  WHERE d.id= :id");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return $data;
  }

  public function updateProperties($array) {
    $this->db->prepare("UPDATE DeviceProperties SET properties= :properties WHERE DeviceId= :id");
    $this->db->bind('properties', $array['properties']);
    $this->db->bind('id', $array['id']);
    $data = $this->db->execute();
    return $data;
  }

} // end class

