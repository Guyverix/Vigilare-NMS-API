<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Site;

use Database;
use App\Domain\Site\Site;
use App\Domain\Site\SiteNotFoundException;
use App\Domain\Site\SiteRepository;


/*
  Generic helper functions to work with CSV and arrays
*/

function normalize_csv(?string $csv): string {
  if ($csv === null) return '';
  $s = trim($csv);
  // strip a single pair of surrounding single quotes if present
  if (strlen($s) >= 2 && $s[0] === "'" && substr($s, -1) === "'") {
      $s = substr($s, 1, -1);
  }
  // remove spaces, collapse duplicate commas, trim commas
  $s = str_replace(' ', '', $s);
  $s = preg_replace('/,+/', ',', $s);
  return trim($s, ',');
}

function csv_to_array(?string $csv): array {
  $clean = normalize_csv($csv);
  if ($clean === '') return [];
  return array_values(array_filter(explode(',', $clean), 'strlen'));
}


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
  }

  // Returns json of id_to_hostname_json as k > v pairing per groupName
  public function getAllHostnamesJson() {
    $results = array();
    // This uses  a NOWDOC so there is no escaping stuff needed.  Nice.. Never heard of this one before
    $sql = <<<'SQL'
SELECT
  ag.groupName,
  CONCAT(
    '{',
    GROUP_CONCAT(
      CONCAT(
        '"', d.id, '":"',
        REPLACE(REPLACE(d.hostname, '\\', '\\\\'), '"', '\"'),
        '"'
      )
      ORDER BY FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', ''))
      SEPARATOR ','
    ),
    '}'
  ) AS id_to_hostname_json
FROM applicationGroup ag
JOIN Device d
  ON FIND_IN_SET(
       d.id,
       REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')
     ) > 0
GROUP BY ag.groupName
ORDER BY ag.groupName;
SQL;
// NOWDOC end delimiter must be newline, no tabs or spaces

    $this->db->prepare("$sql");
    $finalData = $this->db->resultset();
    $errs = $this->db->errorInfo();
    // $results += ['sql' => $sql];
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  // Raw results but id and hostnames cannot be corleated
  public function getAllHostnames() { // no args
    $results = array();
    $sql = "SELECT ag.groupName, ag.deviceId, GROUP_CONCAT(d.hostname ORDER BY FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')) SEPARATOR ', ') AS hostnames FROM applicationGroup ag JOIN Device d ON FIND_IN_SET(d.id, REPLACE(TRIM(BOTH '''' FROM ag.deviceId), ' ', '')) > 0 GROUP BY ag.groupName ORDER BY ag.groupName";
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
    // Assume if we dont have an id, we have hostname or address defined
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


  /*
    This is the only way I have been able to consistently get error
    information and codes.  Sucks, but it is what it is
  */
  public function addGroupName($arr): array { // group needed.  This does NOT add hosts
    $results = array();
    $sql = "INSERT INTO applicationGroup VALUES(:group, '')";
    $this->db->prepare("$sql");
    $this->db->bind('group', $arr['group']);
    $finalData = $this->db->execute();
    // str_contains is PHP 8+
    // execute just returns bool true if the insert works
    if (! is_bool($finalData) && str_contains($finalData, 'errorInfo')) {
      $errDetails = json_decode($finalData, true);
      $errs = $this->db->errorInfo();
      $errCode = $errDetails['errorInfo'][0];
    }
    else {
      $errs = $this->db->errorInfo();
      $errCode = $this->db->errorCode();
    }
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
    $finalData = $this->db->execute();
    $errs = $this->db->errorInfo();
    $results += ['result' => $finalData];
    $results += ['errors' => $errs ];
    return $results;
  }

  public function addHostname($arr): array { // id group needed
    // id can be a single host or an array of hosts
    $results = array();
    if (is_int($arr['id'])) {
      $sql = "UPDATE applicationGroup SET deviceId = CONCAT_WS(',', TRIM(BOTH ',' FROM deviceId), :id) WHERE groupName = :group AND FIND_IN_SET(:id, TRIM(BOTH ',' FROM deviceId)) = 0";
      $this->db->prepare("$sql");
      $this->db->bind('id', $arr['id']);
      $this->db->bind('group', $arr['group']);
      $finalData = $this->db->execute();
      $errs = $this->db->errorInfo();
      $results += ['result' => $finalData];
      $results += ['errors' => $errs ];
      return $results;
    }
    else {
      $getCsvSql = "SELECT deviceId FROM applicationGroup WHERE groupName = :group LIMIT 1";
      $this->db->prepare("$getCsvSql");
      $this->db->bind('group', $arr['group']);
      $getCsvList = $this->db->resultsetArray();
      if (empty($getCsvList)) {
        $getCsvList[0]['deviceId'] = '';
      }
      $existList = csv_to_array($getCsvList[0]['deviceId']);
      if (! is_array($arr['id'])) {
        $arr['id'] = csv_to_array($arr['id']);
      }
      $merged = array_values(array_unique(array_merge($arr['id'], $existList)));
      $csv = implode(',', $merged);
      $sql = "UPDATE applicationGroup SET deviceId =:csv WHERE groupName =:groupFinal";
      $this->db->prepare("$sql");
      $this->db->bind('csv', $csv);
      $this->db->bind('groupFinal', $arr['group']);
      $finalData = $this->db->execute();
      $errs = $this->db->errorInfo();
      $results += ['result' => $finalData];
      $results += ['errors' => $errs ];
      return $results;
    }
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

