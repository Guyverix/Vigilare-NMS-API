<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\GlobalMapping;

use App\Domain\GlobalMapping\GlobalMapping;
use App\Domain\GlobalMapping\GlobalMappingNotFoundException;
use App\Domain\GlobalMapping\GlobalMappingRepository;

/*
  Ignore the damn dependency injection as it is a PITA
  just get the database creds in place so we can query
*/
require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseGlobalMappingRepository implements GlobalMappingRepository {
  private $checkName;
  private $component;
  private $hostGroup;
  private $hostname;
  private $type;
  private $address;
  private $name;
  private $value;
  private $checkAction;
  private $iteration;
  private $storage;
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  public function createGlobalMappingHost($array) {
    $this->db->prepare("INSERT INTO Device VALUES(NULL, :hostname, :address, NOW(), 1)");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $data = $this->db->resultset();
    return $data;
  }

  public function createGlobalMappingHostGroup($array) {
    $this->db->prepare("INSERT INTO DeviceGroup VALUES(NULL, :hostGroup, :hostname )");
    if (empty($array['hostname'])) { $array['hostname'] = ''; }
    $this->db->bind('hostGroup', $array['hostGroup']);
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function createGlobalMappingHostAttribute($array) {
    $this->db->prepare("INSERT INTO hostAttribute VALUES(NULL, :hostname, :component, :name, :value )");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('component', $array['component']);
    $this->db->bind('name', $array['name']);
    $this->db->bind('value', $array['value']);
    $data = $this->db->resultset();
    return $data;
  }

  public function createGlobalMappingPoller($array) {
    $this->db->prepare("INSERT INTO monitoringDevicePoller VALUES(NULL, :checkName, :checkAction, :type, :iteration, :storage, :hostname, :hostGroup ) " );
    $this->db->bind(':checkName', $array['checkName']);
    $this->db->bind(':checkAction', $array['checkAction']);
    $this->db->bind(':type', $array['type']);
    $this->db->bind(':iteration', $array['iteration']);
    $this->db->bind(':storage', $array['storage']);
    $this->db->bind(':hostname', $array['hostname']);
    $this->db->bind(':hostGroup', $array['hostGroup']);
    $data = $this->db->resultset();
    $array['result'] = "Successful insertion of values into monitoringDevicePoller table";
    return $array['result'];
  }

  public function createGlobalMappingTemplate() {
    $data = "Not implemented";
    return $data;
  }

  public function createGlobalMappingTrap($array) {
    $this->db->prepare("INSERT INTO trapEventMap (oid, display_name, severity, pre_processing, type, parent_of, child_of, age_out, post_processing) VALUES(:oid, :display_name, :severity, :pre_processing, :type, :parent_of, :child_of, :age_out, :post_processing )");
    $this->db->bind('oid', $array['oid']);
    $this->db->bind('display_name', $array['display_name']);
    $this->db->bind('severity', $array['severity']);
    $this->db->bind('pre_processing', $array['pre_processing']);
    $this->db->bind('type', $array['type']);
    $this->db->bind('parent_of', $array['parent_of']);
    $this->db->bind('child_of', $array['child_of']);
    $this->db->bind('age_out', $array['age_out']);
    $this->db->bind('post_processing', $array['post_processing']);
    $data = $this->db->resultset();
    return "Insert complete for " . $array['oid'];
  }

  public function updateGlobalMappingHost($array) {
    $this->db->prepare("UPDATE Device SET hostname=:hostname, address=:address monitor=:monitor WHERE hostname=:old_hostname AND address=:old_address ");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('monitor', $array['monitor']);
    $this->db->bind('old_hostname', $array['old_hostname']);
    $this->db->bind('old_address', $array['old_address']);
    $data = $this->db->resultset();
    return $data;
  }

  public function updateGlobalMappingHostGroup($array) {
    $this->db->prepare("UPDATE hostGroup SET hostGroup=:hostGroup, hostname=:hostname WHERE hostGroup=:old_hostGroup ");
    $this->db->bind('hostGroup', $array['hostGroup']);
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('old_hostGroup', $array['old_hostGroup']);
    $data = $this->db->resultset();
    return $data;
  }

  public function updateGlobalMappingHostAttribute($array) {
    // $this->db->prepare("UPDATE hostAttribute SET hostname=:hostname, component=:component, name=:name, value=:value WHERE hostname=:old_hostname AND component=:old_comonent AND name=:old_name ");
    $this->db->prepare("UPDATE hostAttribute SET hostname=:hostname, component=:component, name=:name, value=:value WHERE id=:id ");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('component', $array['component']);
    $this->db->bind('name', $array['name']);
    $this->db->bind('value', $array['value']);
    $this->db->bind('id', $array['id']);
    $this->db->execute();
    $this->db->prepare("SELECT * FROM hostAttribute WHERE id=:id ");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return $data;
  }

/*

  public function updateGlobalMappingPollerHost($array) {
    $query='SELECT checkName, type, storage FROM monitoringDevicePoller WHERE checkName= ' .  $array['checkName'] . ' hostid LIKE \'' . $array['id'] . '\' OR hostid LIKE \'%,' . $array['id'] . ',%\' OR hostid LIKE \'%,' .$array['id'] . '\' OR hostid LIKE \'' . $array['id'] . ',%\'';
    $this->db->prepare("SELECT * FROM monitoringDevicePoller WHERE 
    // First see if we are adding or removing a host
    if ( $array['action'] == "add") {
    }
    else {
    }
    //$this->db->prepare("UPDATE monitoringPoller SET checkName=:checkName , checkAction=:checkAction , type=:type , iteration=:iteration , storage=:storage , hostGroup=:hostGroup , hostname=:hostname WHERE checkName=:old_checkName AND type=:old_type AND iteration=:old_iteration AND storage=:old_storage ");
    $this->db->prepare("UPDATE monitoringDevicePoller SET checkName=:checkName , checkAction=:checkAction , type=:type , iteration=:iteration , storage=:storage , hostGroup=:hostGroup , hostname=:hostname WHERE id=:id ");
    $this->db->bind('checkName', $array['checkName']);
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return "Update complete for monitor checkName: " . $array['checkName'] . " adding or deleting hostId " . $array['id'];
  }

*/


  public function updateGlobalMappingPoller($array) {
    //$this->db->prepare("UPDATE monitoringPoller SET checkName=:checkName , checkAction=:checkAction , type=:type , iteration=:iteration , storage=:storage , hostGroup=:hostGroup , hostname=:hostname WHERE checkName=:old_checkName AND type=:old_type AND iteration=:old_iteration AND storage=:old_storage ");
    $this->db->prepare("UPDATE monitoringDevicePoller SET checkName=:checkName , checkAction=:checkAction , type=:type , iteration=:iteration , storage=:storage , hostGroup=:hostGroup , hostname=:hostname WHERE id=:id ");
    $this->db->bind('checkName', $array['checkName']);
    $this->db->bind('checkAction', $array['checkAction']);
    $this->db->bind('type', $array['type']);
    $this->db->bind('iteration', $array['iteration']);
    $this->db->bind('storage', $array['storage']);
    $this->db->bind('hostGroup', $array['hostGroup']);
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return "Update complete for monitor check name: " . $array['checkName'];
//    return $data;
  }

  public function updateGlobalMappingTemplate() {
    $data = "Not implemented";
    return $data;
  }

  public function updateGlobalMappingTrap($array) {
    $array['type'] = 1; // we do not use string for this table value, duh!
    $this->db->prepare("SELECT count(*) as count FROM trapEventMap WHERE oid = :oid");
    $this->db->bind('oid', $array['oid']);
    $this->db->execute();
    $testExist = $this->db->resultset();
    $testExist = json_decode(json_encode($testExist), true);
    $testResult='';
    foreach($testExist as $testCount) {
      foreach($testCount as $testKey => $testValue) {
        if ( $testKey == 'count' ) {
          $testResult =$testValue;
        }
      }
    }

    if($testResult == 0 ) {
      $typ='INSERT';
//      $this->db->prepare("INSERT INTO trapEventMap VALUES (oid= :oid, display_name= :display_name, severity= :severity, pre_processing= :pre_processing, type= :type, parent_of= :parent_of, child_of= :child_of, age_out= :age_out, post_processing= :post_processing )");
      $this->db->prepare("INSERT INTO trapEventMap VALUES ( :oid, :display_name, :severity, :pre_processing, :type, :parent_of, :child_of, :age_out, :post_processing )");
      $this->db->bind('oid', $array['oid']);
      $this->db->bind('display_name', $array['display_name']);
      $this->db->bind('severity', $array['severity']);
      $this->db->bind('pre_processing', $array['pre_processing']);
      $this->db->bind('type', $array['type']);
      $this->db->bind('parent_of', $array['parent_of']);
      $this->db->bind('child_of', $array['child_of']);
      $this->db->bind('age_out', $array['age_out']);
      $this->db->bind('post_processing', $array['post_processing']);
    }
    else {
      $typ="UPDATE";
      $this->db->prepare("UPDATE trapEventMap SET display_name= :display_name, severity= :severity, pre_processing= :pre_processing, type= :type, parent_of= :parent_of, child_of= :child_of, age_out= :age_out, post_processing= :post_processing WHERE OID= :oid ");
      $this->db->bind('oid', $array['oid']);
      $this->db->bind('display_name', $array['display_name']);
      $this->db->bind('severity', $array['severity']);
      $this->db->bind('pre_processing', $array['pre_processing']);
      $this->db->bind('type', $array['type']);
      $this->db->bind('parent_of', $array['parent_of']);
      $this->db->bind('child_of', $array['child_of']);
      $this->db->bind('age_out', $array['age_out']);
      $this->db->bind('post_processing', $array['post_processing']);
    }
    $this->db->execute();
    $data = $this->db->resultset();

//    return $testResult;
//    return array_values($testExist[0]);
//    return $this->db->prepareDump();
    return $typ . " complete for " . $array['oid'] . print_r($this->db->errorInfo());
  }


  // When deleting a host, we gotta get rid of the attributes too
  public function deleteGlobalMappingHost($array) {
    $this->db->prepare("DELETE FROM Device WHERE hostname=:hostname ");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->execute();
    $data = $this->db->resultset();
    $this->db->prepare("DELETE FROM hostAttribute WHERE hostname=:hostname ");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->execute();
    $data2 = $this->db->resultset();
    return $data;
  }

  public function deleteGlobalMappingHostGroup($array) {
    $this->db->prepare("DELETE FROM DeviceGroup WHERE hostGroup=:hostGroup ");
    $this->db->bind('hostGroup', $array['hostGroup']);
    $data = $this->db->resultset();
    return $data;
  }

  public function deleteGlobalMappingHostAttribute($array) {
    $this->db->prepare("DELETE FROM hostAttribute WHERE hostname=:hostname ");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->execute();
    $data = $this->db->resultset();
    return $data;
  }

  public function deleteGlobalMappingPoller($array) {
    $this->db->prepare("DELETE FROM monitoringDevicePoller WHERE id=:id ");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
//    return $data;
//    return $array;
    return "Deletion complete for monitor id: " . $array['id'];
  }

  public function deleteGlobalMappingTemplate() {
    $data = "Not implemented";
    return $data;
  }

  public function deleteGlobalMappingTrap($array) {
    $this->db->prepare("DELETE FROM trapEventMap WHERE oid= :oid ");
    $this->db->bind('oid' , $array['oid']);
    $this->db->execute();
    $data = $this->db->resultset();
    return "Delete complete for " . $array['oid'];
  }

  public function viewGlobalMappingHost() {
    $this->db->prepare("SELECT hostname, address, monitor from Device");
    $data = $this->db->resultset();
    return $data;
  }

  public function viewGlobalMappingHostGroup() {
    $this->db->prepare("SELECT hostgroupName, hostname FROM DeviceGroup");
    $data = $this->db->resultset();
    return $data;
  }

  public function viewGlobalMappingHostAttribute() {
    $this->db->prepare("SELECT * from hostAttribute");
    $data = $this->db->resultset();
    return $data;
  }

  public function viewGlobalMappingPoller() {
    $this->db->prepare("SELECT * from monitoringDevicePoller");
    $data = $this->db->resultset();
    return $data;
  }

  public function viewGlobalMappingTemplate() {
    $data = "Not implemented";
    return $data;
  }

  public function viewGlobalMappingTrap() {
    $this->db->prepare("SELECT * FROM trapEventMap");
    $data = $this->db->resultset();
    return $data;
  }

  public function findGlobalMappingHost($array) {
    if ( array_key_exists('hostname', $array)) {
      $query='hostname';
      $filter=$array['hostname'];
    }
    elseif (array_key_exists('address', $array)) {
      $query='address';
      $filter=$array['address'];
    }
    else {
      $data='Only hostname and address can be used here';
      return $array;
    }
    $this->db->prepare("SELECT * from Device WHERE $query=:filter");
    $this->db->bind('filter', $filter);
    $data = $this->db->resultset();
    return $data;
  }

  public function findGlobalMappingHostGroup($array) {
    $this->db->prepare("SELECT devicegroupName, hostname FROM DeviceGroup");
    $data = $this->db->resultset();
    return $data;
  }

  public function findGlobalMappingHostAttribute($array) {
    if ( ! empty($array['id'])) {
      $this->db->prepare("SELECT * from hostAttribute WHERE id= :id");
      $this->db->bind(':id', $array['id']);
      $data = $this->db->resultset();
    }
    elseif ( ! empty($array['hostname'])) {
      $this->db->prepare("SELECT * from hostAttribute WHERE hostname= :hostname");
      $this->db->bind(':hostname', $array['hostname']);
      $data = $this->db->resultset();
    }
    else {
      $data = "Either id or hostname values are manditory to find attributes";
    }
    return $data;
  }

  public function findGlobalMappingPoller($array) {
    $this->db->prepare("SELECT * from monitoringDevicePoller WHERE id=:id ");
    $this->db->bind(':id', $array['id']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findGlobalMappingTrap($array) {
    $this->db->prepare("SELECT * FROM trapEventMap");
    $data = $this->db->resultset();
    return $data;
  }

  public function findGlobalMappingTemplate() {
    $data = "Not implemented";
    return $data;
  }
} // end class

