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

  // Works for both hosts as well as hostGroups
  private function newMonitorAddHost($arr): array {
    /*
      Pull existing table data from storage table monitoringDevicePoller
      Transitioning to a storage table for definitions not host mappings
      Thats why not pulling hostId or hostGroup for information here.

      REMEMBER: hostId wins over hostGroup
    */
    // $sql = "SELECT checkName, checkAction, type, iteration, storage, visible FROM monitoringDevicePoller WHERE id= {$arr['id']}";   // Old way, only do this in testing, never live.
    $sql = "SELECT checkName, checkAction, type, iteration, storage, visible FROM monitoringDevicePoller WHERE id= :storageId";
    $this->db->prepare("$sql");
    $this->db->bind('storageId', $arr['id']);
    $retCheckName = json_decode(json_encode($this->db->resultset(),1),true);

    /*
      Break out if we did not get an existing monitor
      Otherwise override whatever we may already have
      in a variable as this needs to match storage
    */
    if ( empty($retCheckName)) { return [ false ]; }
    $arr['checkName'] = $retCheckName[0]['checkName'];
    $arr['checkAction'] = $retCheckName[0]['checkAction'];
    $arr['type'] = $retCheckName[0]['type'];
    $arr['iteration'] = $retCheckName[0]['iteration'];
    $arr['storage'] = $retCheckName[0]['storage'];
    $arr['visible'] = $retCheckName[0]['visible'];

    // Sins of the past catch up to me... Sigh...
    if ( empty($arr['hostId'])) { $arr['hostId'] = $arr['hostid']; }

    // JIC we get cruft in the array
    $arr['hostId'] = trim($arr['hostId']);

    // get the array if it is defined else set to an empty value
    $hostArr = !empty($arr['hostId']) ? explode(',', $arr['hostId']) :[];

    // hostGroup will ALWAYS be empty for this query
    $arr['hostGroup'] = '';

    /*
      Alarm is the beginning of where we set our alarm thresholds and values
      Likely we are also looking at actual code here as well to define
      what variables we want from the deviceProperties values.
      This is more of a template than the actual values themselves.
      Different hosts can have different settings for the same service check.

      Screw de-duplication! We need easy ways to fix screwups at the single host level
      that an Ops person can perform without being a DBA. (assuming they have access)
    */
    $arr['alarm'] = $arr['alarm'] ?? 'basic' ;
    foreach ($hostArr as $singleHost) {
      /*
        If our definition is already present either due to looping
        or foolishness, we are not going to error but simply
        continue on.  Unqueness is poller, checkName, hostid
      */
      $sql = "INSERT INTO activeMonitors VALUES('', :poller, :checkName, :checkAction, :alarm, :type, :iteration, :storage, :singleHost, :visible, '') ON DUPLICATE KEY UPDATE checkName= :checkNameEnd";
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['poller']);
      $this->db->bind('checkName', $arr['checkName']);
      $this->db->bind('checkAction', $arr['checkAction']);
      $this->db->bind('type', $arr['type']);
      $this->db->bind('iteration', $arr['iteration']);
      $this->db->bind('storage', $arr['storage']);
      $this->db->bind('singleHost', $singleHost);
      $this->db->bind('visible', $arr['visible']);
      $this->db->bind('alarm', $arr['alarm']);
      $this->db->bind('checkNameEnd', $arr['checkName']);
      $this->db->execute();
    }
    // This will require more validation and return more information in the future
    return $arr;
  }

  /*
    This is discrete from hostId as we do not instert hostIds that are already defined
    as they take precedence over a group.  Are simply adding the string for the hostGroup
    into the hostid value.
  */
  private function newMonitorAddHostGroup($arr): array {
    // The old system does not have any concept of alarms, so we will have an empty value by default
    $arr['alarm'] = $arr['alarm'] ?? "basic" ;
    $arr['visible'] = $arr['visible'] ?? "no";

    /*
      We know hostGroup is not empty due to the function being called at all
      so we convert the string to an array for looping (N+1 possible hostGroups per monitor)

      Remember hostId wins over hostGroup inserts ALWAYS
    */
    $hostGroupArr = explode(',', $arr['hostGroup']);
    foreach ($hostGroupArr as $hostGroupSingle) {
      $hostGroupSingle = trim($hostGroupSingle);  // Remove whitespace (sneaks in via bruno tests.. sigh..)
      $sql = "SELECT * FROM activeMonitors WHERE poller= :poller AND checkName= :checkName AND hostid= :hostId LIMIT 1";
      /*
        There had better only ever be one return or there was no point to this madness.  CheckName is always unique per poller.
        We are going to leverage hostId to  contain the hostGroup name if we are dealing with many hosts.
      */
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['poller']);
      $this->db->bind('checkName', $arr['checkName']);
      $this->db->bind('hostId', $hostGroupSingle);
      $existingMonitor = json_decode(json_encode($this->db->resultset(),1), true); // Run the query and save as array
      $monitorExistHost = $this->db->rowCount();   // check if monitor is already set for hostId
      /*
        It is critical to remember this ONLY adds new hostGroups to the table.  No other variable changes unless it never existed
        to begin with.
      */
      if ( $monitorExistHost == 0 ) {
        $sql = "INSERT INTO activeMonitors VALUES('', :poller, :checkName, :checkAction, :alarm, :type, :iteration, :storage, :hostId, :visible, '')";
        $this->db->prepare("$sql");
        $this->db->bind('poller', $arr['poller']);
        $this->db->bind('checkName', $arr['checkName']);
        $this->db->bind('checkAction', $arr['checkAction']);
        $this->db->bind('alarm', $arr['alarm']);
        $this->db->bind('type', $arr['type']);
        $this->db->bind('iteration', $arr['iteration']);
        $this->db->bind('storage', $arr['storage']);
        $this->db->bind('visible', $arr['visible']);
        $this->db->bind('hostId', $hostGroupSingle);
        $this->db->execute();
      }
    }
    return $arr;
  }

  /*
    Logically this is going to be somewhat painful.
    We only want to alter stuff specifically when the poller
    IP address is changed.  This means we need to know the three things
    to care about.  poller, checkName, hostid

    Only change when those three match, then update with newPoller value

    Only allow this if we have both (poller or oldPoller) and newPoller defined
  */
  private function newMonitorChangePoller($arr): array {
    $hostIdArr = explode(',', $arr['hostid']);
    $data = array();
    foreach ($hostIdArr as $singleHostId) {
      $singleHostId = trim($singleHostId);
      $sql = "SELECT id FROM activeMonitors WHERE poller= :poller AND checkName= :checkName AND hostid= :hostid";
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['oldPoller']);
      $this->db->bind('checkName', $arr['checkName']);
      $this->db->bind('hostid', $singleHostId);
      $idList = json_decode(json_encode($this->db->resultset(),1), true);  // convert to simple array instead of object

//      $data[] = "hostList " . print_r($idList, true);
      $data[] = "hostList " . json_encode($idList,1);
      $data += $arr;
      // Only worry about updates if we have something to update
      if ( ! empty($idList[0])) {
        $sqlUpdate = "UPDATE activeMonitors SET poller= :newPoller WHERE id= :id";
        $this->db->prepare("$sqlUpdate");
        $this->db->bind('newPoller', $arr['newPoller']);
        $this->db->bind('id', $idList[0]['id']);
        $this->db->execute();
      }
    }
    //   return $data;

    // Hostgroups are single in this table, but an array in the old one so we need to loop against hostid
    $hostGroupArr = explode(',', $arr['hostGroup']);
    foreach ($hostGroupArr as $singleHostId) {
      $singleHostId = trim($singleHostId);
      $sql = "SELECT id FROM activeMonitors WHERE poller= :poller AND checkName= :checkName AND hostid= :hostid";
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['oldPoller']);
      $this->db->bind('checkName', $arr['checkName']);
      $this->db->bind('hostid', $singleHostId);
      $idList = json_decode(json_encode($this->db->resultset(),1), true);
      $data[] = "hostGroupList " . print_r($idList, true);
      if ( ! empty($idList[0])) {
        $sqlUpdate = "UPDATE activeMonitors SET poller= :newPollerHG WHERE id= :idHG";
        $this->db->bind('newPollerHG', $arr['newPoller']);
        $this->db->bind('idHG', $id);
        $this->db->execute();
      }
    }
    $arr['results'] = $data;
    return $arr;
  }

  /*
    The only thing this is going to change is the monitor itself
    it will NOT alter hostid or groupName.

    This does require the hostid to be defined so we know what
    monitor we are going to update.
    UNIQUE: poller, checkName, hostid
  */
  private function newMonitorChangeMonitor($arr):array {
    $arr['alarm'] = $arr['alarm'] ?? "basic" ;
    $arr['visible'] = $arr['visible'] ?? "no";

    // If we are changing pollers, or checkNames they must be defined before binding
    $arr['newPoller'] = $arr['newPoller'] ?? $arr['poller'];
    $arr['oldPoller'] = $arr['oldPoller'] ?? $arr['poller'];
    $arr['newCheckName'] = $arr['newCheckName'] ?? $arr['checkName'];

    // we can get a CSV of hosts...
    $hostArr = explode(',', $arr['hostid']);
    foreach($hostArr as $singleHost) {
      $sql = "UPDATE activeMonitors SET poller= :poller, checkName= :checkName, checkAction= :checkAction, alarm= :alarm, type= :type, iteration= :iteration, storage= :storage visible= :visible WHERE poller= :filterPoller AND checkName= :filterCheckName AND hostid= :hostid"; 
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['newPoller']);
      $this->db->bind('checkName', $arr['newCheckName']);
      $this->db->bind('checkAction', $arr['checkAction']);
      $this->db->bind('alarm', $arr['alarm']);
      $this->db->bind('type', $arr['type']);
      $this->db->bind('iteration', $arr['iteration']);
      $this->db->bind('storage', $arr['storage']);
      $this->db->bind('visible', $arr['visible']);
      $this->db->bind('filterPoller', $arr['oldPoller']);
      $this->db->bind('filterCheckName', $arr['checkName']);
      $this->db->bind('hostid', $singleHost);
      $this->db->execute();
    }
    return $arr;
  }

  private function newMonitorDeleteHost($arr) {
    /*
      Remove a specific host from monitoring something.
    */
    $arr['hostId'] = $arr['hostId'] ?? $arr['hostid'];
    $sql = "DELETE FROM activeMonitors WHERE poller= :poller, checkName= :checkName, hostid= :hostId LIMIT 1";
    $this->db->prepare("$sql");
    $this->db->bind('poller', $arr['poller']);
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->bind('hostId', $arr['hostId']);
    $this->db->execute();
    return $arr;
  }

  private function newMonitorDeleteHostGroup($arr) {
    /*
      Same as removing a hostId, since they are the same column now.  However keep the function
      seporate in case we change this behavior in the future.
      We cannot assume that we did not get an array of hostGroups, so always explode the group
    */
    $arr['hostGroup'] = $arr['hostGroup'] ?? '';
    $hostGroupList = explode(',', $arr['hostGroup']);
    foreach($hostGroupList as $hostGroupSingle) {
      $sql = "DELETE FROM activeMonitors WHERE poller= :poller, checkName= :checkName, hostid= :hostId LIMIT 1";
      $this->db->prepare("$sql");
      $this->db->bind('poller', $arr['poller']);
      $this->db->bind('checkName', $arr['checkName']);
      $this->db->bind('hostId', $hostGroupSingle);
      $this->db->execute();
    }
    return $arr;
  }

  /*
    Cleanup of API logic.  KISS dammit!
    Get this down to the basics and simply leverage private functions to do the work
    CRUD
  */

  private function newMonitorAddHost2($arr): array {
  }

  private function newMonitorAddHostGroup2($arr): array {
  }

  private function newMonitorDeleteHost2($arr): array {
  }


  // add hostGroups THEN host if defined.  Host always wins for default
  private function newAddMonitor($arr): array {
  }

  /*
    Change monitor cannot add hosts on update.  This should be
    only changes to the monitor itself.
  */
  private function newUpdateMonitor($arr): array {
  }

  /*
     alteration of hostGroup and hosts for monitoring.  Host always wins for default
     hostGroup canno remove host if host defined without hostgroup value also existing.
     Use caution, you can have a host defined in multiple groups and have that show up.
  */
  private function newUpdateMonitorHosts($arr): array {
  }

  /*
    Delete is against the new table and removes where
    poller and checkName match.
    This one is slightly more dangerous as it does not
    take into account one-off monitors for a single host
    vs a hostGroup in the table.
  */
  private function newDeleteMonitor($arr): array {
    $sql = "DELETE FROM activeMonitors WHERE poller= :poller, checkName= :checkName";
    $this->db->prepare("$sql");
    $this->db->bind('poller', $arr['poller']);
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->execute();
    return $arr;
  }

  /*
    This will remove a host, but not the definition of it in DeviceGroup
    Iterate through all hosts to remove them if a list
  */
  private function newDeleteMonitorHost($arr): array {
  }

  /*
    Remove from DeviceGroup table an entire hostGroup
    This must be done AFTER newDeleteMonitorHost so there are no orphans
  */
  private function newDeleteMonitorHostGroup($arr): array {
  }

  /*
    Attempt to make a mapping of whatever we are being given.
    Default will be small, but allow for overrides for complex mappings
    if someone when to the early trouble to write them (future)
  */
  private function addTrapEventMap($arr): array {
    $results = array();
    $this->db->prepare("SELECT oid FROM trapEventMap WHERE oid= :checkName");
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->execute();
    $matches = $this->db->rowCount();
    $errs = '';
    if ( $matches == 0 ) {
      // This is a non-critical insert, so dont sweat it if we do not get a default...
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
    return $results;
  }


  /*
    Older style that is being cleaned up
    Attempting to go for a real KISS solution
    Base logic here will call the private functions to "do something"
    Filtering should be done here and really show what is happening
    and where it is happening.
  */

  // First new version that will leverage private functions and create the new table data
  public function createMonitor($arr): array {
    $results = array();
    $arr['hostId'] = $arr['hostId'] ?? '';
    $arr['hostGroup'] = $arr['hostgroup'] ?? '';
    $arr['visible'] = $arr['visible'] ?? 'yes';
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

    // This is the base monitor old style creation
    if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }
    $results += ['firstResult' => 'Create new monitor ' . $arr['checkName'] . ' '. $rString];
    $results += ['firstError' => $errs];

    // Create a event mapping if it does not already exist
    $this->db->prepare("SELECT oid FROM trapEventMap WHERE oid= :checkName");
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->execute();
    $matches = $this->db->rowCount();

    // Even if this fails, we have the monitor!  Mapping can be done later in the event of failure, but try to set defaults early
    if ( $matches == 0 ) {
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

    /*
      Finally create our NEW table information for monitoring

      Remember Host wins against hostGroups!  Always
      Any specific hostId will ALWAYS be added and NOT have hostGroup set
      Any specific hostGroup that is set will ONLY insert if there is no hostId with that checkName
    */
    if (! empty($arr['hostId'])) {
      $addHost = self::newCreateMonitorHost($arr);
    }

    if (! empty($arr['hostGroup'])) {
      $addHostGroup = self::newCreateMonitorHostGroup($arr);
    }
    return $results;
  }

  // retired old initial code to create a monitor
  private function oldCreateMonitor($arr): array {
    $results = array();
    $arr['hostId'] = $arr['hostId'] ?? '';
    $arr['hostGroup'] = $arr['hostGroup'] ?? '';
    $arr['visible'] = $arr['visible'] ?? 'no' ;

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

    $rString = ($data == 1) ? 'complete' : 'error';
    // if ( $data == 1 ) { $rString = 'complete'; } else { $rString = 'error'; }  // above makes more sense than if clause
    $results += ['firstResult' => 'Create new monitor ' . $arr['checkName'] . ' '. $rString];
    $results += ['firstError' => $errs];

    /*
      Attempt to see if we have a mapping defined for the checks
      If not, then create a basic mapping
    */
    $addTrap = self::addTrapEventMap($arr);
    $results += ['secondResult' => $addTrap['secondResult'] ];
    $results += ['secondError' => $addTrap['secondError'] ];

    /*
      Now that we have the monitor defined, we add to the new table
      both the hostId as well as any hostGroups that may have been
      defined at creation.
    */

    $sql = "SELECT * FROM monitoringDevicePoller WHERE checkName= :checkName, type= :type, iteration= :iteration, storage= :storage, checkAction= :checkAction, visible= :visible";
    $this->db->prepare("$sql");
    $this->db->bind('checkName', $arr['checkName']);
    $this->db->bind('type', $arr['type']);
    $this->db->bind('iteration', $arr['iteration']);
    $this->db->bind('checkAction', $arr['checkAction']);
    $this->db->bind('storage', $arr['storage']);
    $this->db->bind('visible', $arr['visible']);
    $monitorResults = json_decode(json_encode($this->db->resultset(),1), true);
    // The result should only have 1 row returned and contain everything defined.
    if (! empty($monitorResults['hostId'])) {
      $updateHost = self::newMonitorAddHost($monitorResults);
      $results += ['newTableHostResult' => "complete" ];
    }

    if (! empty($monitorResults['hostGroup'])) {
      $updateHostGroup = self::newMonitorAddHostGroup($monitorResults);
      $results += ['newTableHostGroupResult' => "complete" ];
    }

    /*

    // get the array if it is defined else set to an empty value
    $hostArr = !empty($arr['hostId']) ? explode(',', $arr['hostId']) :[];
    foreach ($hostArr as $singleHost) {
      $sql = "INSERT INTO activeMonitors VALUES('', {$arr['poller']}, {$arr['checkName']}, {$arr['checkAction']}, 'basic', {$arr['type']}, {$arr['iteration']}, {$arr['storage']}, {$singleHost}, {$arr['visible']} ) ON DUPLICATE KEY UPDATE checkName= {$arr['checkName']} ";
      $this->db->prepare("$sql");
      $this->db->execute();
    }
    // do the same for hostGroups
    $hstGroupArr=explode(',', $arr['hostGroup']);
    foreach ($hstGroupArr as $hstGrp) {
      $sql = "SELECT hostname FROM DeviceGroup WHERE devicegroupName= $hstGrp LIMIT 1";
      $this->db->prepare("$sql");
      $dataArr = json_decode(json_encode($this->db->resultset(),1),true);
      // get the array if it is defined else set to an empty value
      $hostArr = !empty($dataArr[0]['hostname']) ? explode(',', $dataArr[0]['hostname']) :[];
      foreach ($hostArr as $singleHost) {
        $sql = "INSERT INTO activeMonitors VALUES('', {$arr['poller']}, {$arr['checkName']}, {$arr['checkAction']}, 'basic', {$arr['type']}, {$arr['iteration']}, {$arr['storage']}, {$singleHost}, {$arr['visible']}) ON DUPLICATE KEY UPDATE checkName= {$arr['checkName']}";
        $this->db->prepare("$sql");
        $this->db->execute();
      }
    }
    */
    $results += ['result' => 'Create new monitor ' . $arr['checkName'] . ' ' . $rString];
    $results += ['errors' => $errs];
    return $results;
  }

  public function monitorAddHost($arr): array {
    $results = array();
    /*
      New table code.  Placeholder to test.  Will switch to binding later
    */
    $addNewMonitoring = self::newMonitorAddHost($arr);
    /*
      Back to old style in old table
    */

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

  /*
    This is something of a hammer to change stuff.  It assumes overwrite of existing values
  */

  public function updateMonitor($arr): array {
    $results = array();
    $sql = "SELECT * FROM monitoringDevicePoller WHERE id= :searchId LIMIT 1";
    $this->db->prepare("$sql");
    $this->db->bind('searchId', $arr['id']);
    $existValues = json_decode(json_encode($this->db->resultset(),1),true);
    // Set defaults if they are not updated...
    $arr['checkName'] = $arr['checkName'] ?? $existValues[0]['checkName'];
    $arr['checkAction'] = $arr['checkAction'] ?? $existValues[0]['checkAction'];
    $arr['type'] = $arr['type'] ?? $existValues[0]['type'];
    $arr['iteration'] = $arr['iteration'] ?? $existValues[0]['iteration'];
    $arr['storage'] = $arr['storage'] ?? $existValues[0]['storage'];
    $arr['hostid'] = $arr['hostid'] ?? $existValues[0]['hostid'];
    $arr['hostGroup'] = $arr['hostGroup'] ?? $existValues[0]['hostGroup'];
    $arr['newPoller'] = $arr['poller'] ?? '255.255.255.255';   // New system must have some kind of poller set!
    // return $arr;  // debugging

    /*
      New table code.
    */
    /*
       Remove hostgroups and we will re-add with changes
       This will leave single hosts alone
    */
    if (! empty($arr['hostGroup'])) {
      $cleanDeleteHostGroups = self::newMonitorDeleteHostGroup($arr);
      $cleanAddHostGroups = self::newMonitorAddHostGroup($arr);
    }
    /*
      Only update hostId when hostgroup is not defined AND when hostId is not a string value
      but an integer only.  This will keep us from clobbering a custom host value when we are
      working with it.  Custom settings per host must never be touched when working with
      a hostGroup as well.

      Default monitor is expecting a list for hostgroup to run generic stuff and adhoc oddballs
      will be defined on a per hostId = (int) basis.

    */

    if (! empty($arr['hostid']) && empty($arr['hostGroup'])) {
      $singleHostId = explode(',', $arr['hostid']);
//      $
    }


    // Only change poller if new and old are defined
    if (isset($arr['newPoller']) && isset($arr['oldPoller']) ) {
      $changePoller = self::newMonitorChangePoller($arr);
    }
    else {
      $changePoller = "inputs newPoller and oldPoller were not set.  Polling host not changed";
    }
    $results += ['changePoller' => $changePoller ];
    return $results;
    $changeMonitoring = self::newMonitorChangeMonitor($arr);
    $results += ['changeMonitor' => $changeMonitoring ];
    $arr['poller'] = $arr['newPoller']; // Update the array value here so the hosts can update correctly
    $addNewMonitoringHost = self::newMonitorAddHost($arr);
    $results += ['addnewMonitoringHost' => $addNewMonitoringHost ];
    $addNewMonitoringHostGroup = self::newMonitorAddHostGroup($arr);
    $results += ['addnewMonitoringHostGroup' => $addNewMonitoringHostGroup ];

    return $changeMonitoring;
    // return $addNewMonitoringHost;
    // return $addNewMonitoringHostGroup;
    // return $arr;

    /*
      Back to old style in old table
    */

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

  public function findAlarmCount() {
    $results = array();
    $sql = "SELECT
  /* 1) total host×check targets (direct hosts + group hosts) */
  (
    SELECT SUM(direct_cnt + IFNULL(group_cnt, 0)) AS total_targets
    FROM (
      /* compute targets per poller */
      SELECT
        m.id,
        m.checkName,
        /* count of direct hosts in monitoringDevicePoller.hostid */
        CASE
          WHEN m.clean_hosts = '' THEN 0
          ELSE 1
               + LENGTH(m.clean_hosts)
               - LENGTH(REPLACE(m.clean_hosts, ',', ''))
        END AS direct_cnt,

        /* sum of hosts in each matched DeviceGroup for this poller */
        SUM(
          CASE
            WHEN dg.clean_dg_hosts IS NULL OR dg.clean_dg_hosts = '' THEN 0
            ELSE 1
                 + LENGTH(dg.clean_dg_hosts)
                 - LENGTH(REPLACE(dg.clean_dg_hosts, ',', ''))
          END
        ) AS group_cnt

      FROM (
        /* clean JSON-ish fields once per poller */
        SELECT
          id,
          checkName,
          REPLACE(REPLACE(REPLACE(hostid,    '[',''),']',''),'\"','') AS clean_hosts,   -- e.g.  host1,host2
          REPLACE(REPLACE(REPLACE(hostGroup, '[',''),']',''),'\"','') AS clean_groups   -- e.g.  grpA,grpB
        FROM monitoringDevicePoller
      ) AS m

      /* join to each DeviceGroup whose name appears in the poller’s hostGroup list */
      LEFT JOIN DeviceGroup AS g
        ON m.clean_groups <> ''
       AND CONCAT(',', m.clean_groups, ',') LIKE CONCAT('%,', g.devicegroupName, ',%')

      /* clean the DeviceGroup.hostname list once */
      LEFT JOIN (
        SELECT
          id,
          REPLACE(REPLACE(REPLACE(hostname, '[',''),']',''),'\"','') AS clean_dg_hosts  -- e.g. host3,host4
        FROM DeviceGroup
      ) AS dg
        ON dg.id = g.id

      GROUP BY m.id, m.checkName, m.clean_hosts
    ) AS per_poller
  )                                                   AS total_targets,

  /* 2) total distinct checks defined */
  (SELECT COUNT(DISTINCT checkName) FROM monitoringDevicePoller) AS total_checks,

  /* 3) severity buckets for events whose names match a defined check (treat '-' == '_', case-insensitive) */
  (SELECT SUM(e.eventSeverity = 5)
     FROM `event` e
     WHERE LOWER(REPLACE(e.eventName, '-', '_')) IN
           (SELECT DISTINCT LOWER(REPLACE(checkName, '-', '_')) FROM monitoringDevicePoller)
  ) AS critical,

  (SELECT SUM(e.eventSeverity = 4)
     FROM `event` e
     WHERE LOWER(REPLACE(e.eventName, '-', '_')) IN
           (SELECT DISTINCT LOWER(REPLACE(checkName, '-', '_')) FROM monitoringDevicePoller)
  ) AS error,

  (SELECT SUM(e.eventSeverity = 3)
     FROM `event` e
     WHERE LOWER(REPLACE(e.eventName, '-', '_')) IN
           (SELECT DISTINCT LOWER(REPLACE(checkName, '-', '_')) FROM monitoringDevicePoller)
  ) AS warning,

  (SELECT SUM(e.eventSeverity = 2)
     FROM `event` e
     WHERE LOWER(REPLACE(e.eventName, '-', '_')) IN
           (SELECT DISTINCT LOWER(REPLACE(checkName, '-', '_')) FROM monitoringDevicePoller)
  ) AS info,

  (SELECT SUM(e.eventSeverity = 1)
     FROM `event` e
     WHERE LOWER(REPLACE(e.eventName, '-', '_')) IN
           (SELECT DISTINCT LOWER(REPLACE(checkName, '-', '_')) FROM monitoringDevicePoller)
  ) AS debug,

  /* optional: OK = total_checks - checks with any alarm (severity > 0) */
  (
    (SELECT COUNT(DISTINCT checkName) FROM monitoringDevicePoller)
    -
    (SELECT COUNT(DISTINCT m.checkName)
       FROM monitoringDevicePoller m
       JOIN `event` e
         ON LOWER(REPLACE(e.eventName, '-', '_')) = LOWER(REPLACE(m.checkName, '-', '_'))
        AND e.eventSeverity > 0
    )
  ) AS ok;";
    $this->db->prepare("$sql");
    $data = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $data];
    $results += ['errors' => $errs ];
    return $results;
  }
} // end class
