<?php
declare(strict_types=1);


namespace App\Infrastructure\Persistence\Discover;

/* Called from Domain\Discover\DiscoverRepository */

use App\Domain\Discover\Discover;
use App\Domain\Discover\DiscoverNotFoundException;
use App\Domain\Discover\DiscoverRepository;

require __DIR__ . '/../../../../app/Database.php';
use Database;
use SNMP;
class DatabaseDiscoverRepository implements DiscoverRepository {

  public function __construct() {
    $this->db = new Database();
  }

  public function findHostname($arr) {
    $result=gethostbyaddr($arr['ipAddress']);
    return $result;
  }

  public function setDefaults() {
    $snmpVersion="[1,2]";
    $snmpAuth='snmpAuthV3';
    $snmpPriv='snmpPrivV3';
    $snmpCommunities="[\"public\",\"private\",\"monk2net\"]";
    $snmpPort=161;
    $this->db->prepare("INSERT INTO discover VALUES(NULL, :snmpVersion, :snmpAuth, :snmpPriv, :snmpCommunities, :snmpPort )");
    $this->db->bind('snmpVersion', $snmpVersion);
    $this->db->bind('snmpAuth', $snmpAuth);
    $this->db->bind('snmpPriv', $snmpPriv);
    $this->db->bind('snmpCommunities', $snmpCommunities);
    $this->db->bind('snmpPort', $snmpPort);
    $this->db->execute();
    $result = "Inserted default values for SNMP discovery";
    return $result;
  }

  public function updateDefaults($arr) {
    $snmpVersion=$arr['snmpVersion'];
    $snmpAuth=$arr['snmpAuth'];
    $snmpPriv=$arr['snmpPriv'];
    $snmpCommunities=$arr['snmpCommunities'];
    $snmpPort=$arr['snmpPort'];
    $this->db->prepare("DELETE * FROM discover");
    $this->db->execute();
    $this->db->prepare("INSERT INTO discover VALUES(NULL, :snmpVersion, :snmpAuth, :snmpPriv, :snmpCommunities, :snmpPort )");
    $this->db->bind('snmpVersion', $snmpVersion);
    $this->db->bind('snmpAuth', $snmpAuth);
    $this->db->bind('snmpPriv', $snmpPriv);
    $this->db->bind('snmpCommunities', $snmpCommunities);
    $this->db->bind('snmpPort', $snmpPort);
    $this->db->execute();
    $result = "Overwrote existing values for SNMP discovery with updated values";
    return $result;
  }

  public function findIpAddress($arr) {
    $result=dns_get_record($arr['device'], DNS_A);
    return array_values($result);
  }

  public function findSnmp() {
    $this->db->prepare("SELECT * FROM discover");
    $this->db->execute();
    $result = $this->db->resultset();
    return $result;
  }

  public function dummyWorkingSnmp($arr) {
    return $arr;
  }

  public function workingSnmp($arr) {
    // REMEMBER THIS IS SLOW
    // Use exec since SNMP class returns fatals when SNMP walk fails.  We cannot have that
    $workingCommunity='failed';
    $highestVersion=0;
    $verArr=json_decode($arr['snmpVersion']);
    $commArr=json_decode($arr['snmpCommunities']);
    $addy=$arr['ipAddress'];
    $port=$arr['snmpPort'];
    $priv=$arr['snmpAuth'];
    $key=$arr['snmpPriv'];
    $results=array("community" => "$workingCommunity", "version" => "$highestVersion", "port" => 0);
    // Testing a walk instead, a better get to make very certaion we have legit results
    $snmpOidValue="1.3.6.1.2.1.1";
    $outpug=null;
    $retval=null;
    foreach ( $verArr as $version) {
      // iterate over the versions
      foreach ($commArr as $community) {
        // inside the versions iterate over the comm strings until we match something working
        // echo "VERSION: " . $version . " COMMUNITY ". $community . "\n";
        switch ($version) {
          case 2:
            $session = exec("snmpwalk -v2c -c $community $addy $snmpOidValue", $output, $retval);
            break;
          case 1:
            $session = exec("snmpwalk -v $version -c $community $addy $snmpOidValue", $output, $retval);
            break;
          case 3:
            $session = exec("snmpwalk -v3 -l authPriv -u snmpUser -a SHA -A \"password01\" -x AES -X \"password02\" -c $community $addy $snmpOidValue", $output, $retval);
            break;
        }
        //echo "RETVAL: " . $retval . " " . gettype($retval) . " \n";
        if ($retval == 0) {
          if ( $version > $highestVersion ) {
              $highestVersion = $version;
              $workingCommunity=$community;
          }
        }
      }
    }
    if ( $highestVersion > 0) {
      $results['community'] = "$workingCommunity";
      $results['version'] = "$highestVersion";
      $results['port'] = $port;
    }
    return $results;
  }

  public function validateSnmpSettings($arr) {
    $this->db->prepare("SELECT * FROM hostAttribute WHERE hostname= :hostname OR hostname= :address");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('address', $arr['ipAddress']);
    $this->db->execute();
    $result = $this->db->resultset();
    return $result;
  }

  public function updateSnmpSettings($arr) {
    if($arr['version'] == 2) { $arr['version'] = "v2c"; }
    $snmpSettings=$arr['community'] .', '. $arr['version'] .', '. $arr['port'];
    $this->db->prepare("INSERT INTO hostAttribute VALUES( :hostname, 'SNMP', 'snmpSetup', :snmpSettings ) ON DUPLICATE KEY UPDATE value= :snmpSettings");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('snmpSettings', $snmpSettings);
    $this->db->execute();
    return "Insert SNMP connection values complete";
  }

  public function updateHostGroup($arr) {
    if (empty($arr['tree'])) { $arr['tree'] = 1; }
    if (empty($arr['level'])) { $arr['level'] =1; }
    $this->db->prepare("INSERT INTO hostGroup VALUES( :hostname, :tree, :level ) ON DUPLICATE KEY UPDATE tree= :tree, level= :level");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('tree', $arr['tree']);
    $this->db->bind('level', $arr['level']);
    $this->db->execute();
    return "Insert hostgroup complete";
  }

  // This should be moved to monitors or someplace like that unless we are simply setting a default.
  public function updateMonitorGroup($arr) {
    if (empty($arr['tree'])) { $arr['tree'] = 1; }
    if (empty($arr['level'])) { $arr['level'] = 1; }
    $this->db->prepare("INSERT INTO monitorGroup VALUES( :hostname, :tree, :level ) ON DUPLICATE KEY UPDATE tree= :tree, level= :level");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('tree', $arr['tree']);
    $this->db->bind('level', $arr['level']);
    $this->db->execute();
    return "Insert monitorgroup complete";
  }
}
