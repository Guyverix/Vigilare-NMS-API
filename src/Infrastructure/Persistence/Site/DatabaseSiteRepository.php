<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Site;

use Database;
use App\Domain\Site\Site;
use App\Domain\Site\SiteNotFoundException;
use App\Domain\Site\SiteRepository;

class DatabaseSiteRepository implements SiteRepository {
  public $db = "";

  public function __construct() {
     $this->db = new Database();
  }

  /*
   This is going to have to be adjusted for when we have environments where vars like address collide
   This is for future me to worry about.  I dont have that big of a network currently.
   I am likely going to have to simulate 2 different regional networks where these kinds of collisions can happen
   Additoinally do not forget things like Kubernetes.  That is going to get UGLY as well if they are top tier devices
  */
  public function getId($arr): array { // hostname or address needed to get the ID value
    $results = array();
    if (empty($arr['address'])) { $arr['address'] = $arr['hostname']; }
    if (empty($arr['hostname'])) { $arr['hostname'] = $arr['address']; }
    $sql = "SELECT id FROM Device WHERE hostname= :hostname OR address= :address LIMIT 1";
    $this->db->prepare("$sql");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('address', $arr['address']);
    $finalData = $this->db->resultsetArray();
    //  This is as close to a simple string as we can get :)
    return $finalData[0];
//    return json_decode(json_encode($finalData[0]['id'], true),1);
  }

  public function getAllHostnames() { // no args
    $results = array();
    $sql = "SELECT ag.groupName, GROUP_CONCAT(d.hostname ORDER BY FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')) SEPARATOR ', ') AS hostnames FROM applicationGroup ag JOIN Device d ON FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')) > 0 GROUP BY ag.groupName ORDER BY ag.groupName";
    $this->db->prepare("$sql");
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function getHostnameFromGroupName($arr): array { // group needed
    $results = array();
    $sql = "SELECT d.hostname FROM applicationGroup ag JOIN Device d ON FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')) > 0 WHERE ag.groupName = :group ORDER BY FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', ''))";
    $this->db->prepare("$sql");
    $this->db->bind('group', $arr['group']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function getGroupNamesFromHostname($arr): array { // id needed
    $results = array();
    if (empty($arr['id'])) {
      $transient = self::getId($arr);
      $arr['id'] = $transient['id'];
    }
    $sql = "select groupName from applicationGroup WHERE FIND_IN_SET(:id, TRIM(BOTH '''' FROM deviceId)) > 0";
    $this->db->prepare("$sql");
    $this->db->bind('id', $arr['id']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function addGroupName($arr): array { // group needed.  This does NOT add hosts
    $results = array();
    $sql = "INSERT INTO applicationGroup VALUES(:group, '')";
    $this->db->prepare("$sql");
    $this->db->bind('group', $arr['group']);
//    $finalData = $this->db->resultset();
    $finalData = $this->db->execute();
    $errs = $this->db->errorInfo();
    $errCode = $this->db->errorCode();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    $results += ['code' => $errCode ];
    return $results;
  }

  public function deleteGroupName($arr): array { // nuke a group
    $results = array();
    $sql = "DELETE FROM applicationGroup WHERE groupName=:group";
    $this->db->prepare("$sql");
    $this->db->bind('group', $arr['group']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function addHostname($arr): array { // id group needed
    $results = array();
    $sql = "UPDATE applicationGroup SET deviceId = CONCAT_WS(',', TRIM(BOTH ',' FROM deviceId), :id) WHERE groupName = :group AND FIND_IN_SET(:id, TRIM(BOTH ',' FROM deviceId)) = 0";
    $this->db->prepare("$sql");
    $this->db->bind('id', $arr['id']);
    $this->db->bind('group', $arr['group']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function deleteHostname($arr): array { // id group needed
    $results = array();
    $sql = "UPDATE applicationGroup SET deviceId = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', deviceId, ','), ',:id ,', ',')) WHERE groupName = :group";
    $this->db->prepare("$sql");
    $this->db->bind('id', $arr['id']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  // This is going to go through all groupNames and remove the given ID value in all groups
  public function cleanHostname($arr): array { // id needed
    $results = array();
    $sql = "UPDATE applicationGroup SET deviceId = CONCAT(\"'\", TRIM(BOTH ',' FROM REPLACE(CONCAT(',', TRIM(BOTH '''' FROM REPLACE(deviceId, ' ', '')), ','),',:id,', ',')),\"'\") WHERE FIND_IN_SET(:id, TRIM(BOTH '''' FROM REPLACE(deviceId, ' ', ''))) > 0";
    $this->db->prepare("$sql");
    $this->db->bind('id', $arr['id']);
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  // Testing pathing and sanity
  public function findSiteInvalid($siteRequest): array {
    $results = array();
    $results = ["Invalid API call received"];
    return $results ;
  }
}

