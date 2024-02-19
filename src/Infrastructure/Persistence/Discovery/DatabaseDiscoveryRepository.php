<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Discovery;

use App\Domain\Discovery\Discovery;
use App\Domain\Discovery\DiscoveryNotFoundException;
use App\Domain\Discovery\DiscoveryRepository;
use Database;

class DatabaseDiscoveryRepository implements DiscoveryRepository {
  public $db; 

  public function __construct() {
    $this->db = new Database();
  }

  // This is going to be primary for getting template values back
  public function FindTemplateDefaultDeviceProperties($arr): array {
    $this->db->prepare("SELECT * FROM templates WHERE Class= :Class AND Name= :Name LIMIT 1");
    $this->db->bind('Class', $arr['Class']);
    $this->db->bind('Name', $arr['Name']);
    $data = $this->db->resultset();
    return $data;
  }

  // Search for templateValue by name and device folder for filtering
  public function FindTemplateDeviceProperties($arr):array {
    $this->db->prepare("SELECT * FROM templates WHERE Name= :Name AND DeviceFolder= :DeviceFolder LIMIT 1");
    $this->db->bind('Name', $arr['Name']);
    $this->db->bind('DeviceFolder', $arr['DeviceFolder']);
    $data=$this->db->resultset();
    return $data;
  }

  public function FindDeviceDetail($arr): array {
    $this->db->prepare("SELECT hostname, address FROM Device WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $data=$this->db->resultset();
    return $data;
  }

  public function CreateDevicePropertiesTemplate($arr): array {
    if ( is_array($arr['templateValue'])) { $arr['templateValue']=json_encode($arr['templateValue'],true); }
    $this->db->prepare("INSERT INTO templates VALUES(NULL, :Class, :Name, :templateValue) ON DUPLICATE KEY UPDATE templateValue= :templateValue");
    $this->db->bind('Class', $arr['Class']);
    $this->db->bind('Name', $arr['Name']);
    $this->db->bind('templateValue', $arr['templateValue']);
    $data=$this->db->resultset();
    // $statement=json_decode(json_encode($this->db->statement(),1),True);
    // return $statement;
    return $data;
  }

  public function CreateDeviceFolder($arr): array {
    if ( ! isset($arr['Devices'])) { $arr['Devices']=''; }
    if ( is_array($arr['Devices'])) { $arr['Devices']=json_encode($arr['Devices'],True); }
    $this->db->prepare("INSERT INTO DeviceFolder VALUES(NULL, :DeviceFolder, :Devices) ON DUPLICATE KEY UPDATE Devices= :Devices");
    $this->db->bind('DeviceFolder', $arr['DeviceFolder']);
    $this->db->bind('Devices', $arr['Devices']);
    $data=$this->db->resultset();
    return $data;
  }

  public function CreateDevice($arr): array {
    $this->db->prepare("INSERT INTO Device VALUES(NULL, :hostname, :address, NOW(), :productionState)");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('address', $arr['address']);
    $this->db->bind('productionState', $arr['productionState']);
//    $data=$this->db->resultset();
    return $arr;
  }

  public function FindDeviceSnmpSettings($arr): array {
    /*
      Due to some crappy behavior, we are going to exec out
      to learn the valid SNMP community and version.
    */
    $hostname      =$arr['hostname'];
    $address       =$arr['address'];
    $id            =(int)$arr['id'];
    $communities   =$arr['snmpCommunities'];   // array
    $versions      =$arr['snmpVersions'];         // array
    $timeout       =(int)$arr['snmpMonitorTimeout'];
    $retries       =(int)$arr['snmpTries'];
    if ( isset($arr['snmpCommunity']) && ! empty($arr['snmpCommunity'])) { $communities[] = $arr['snmpCommunity']; }
    if ( isset($arr['v3user'] ))         { $v3user               =$arr['v3user']; } else { $v3user="v3user"; }
    if ( isset($arr['v3level'] ))        { $v3level              =$arr['v3level'];} else { $v3level="v3level"; }
    if ( isset($arr['v3protocol'] ))     { $v3protocol        =$arr['v3protocol'];} else { $v3protocol="v3protocol" ; }
    if ( isset($arr['v3password'] ))     { $v3password        =$arr['v3password'];} else { $v3password="v3password"; }
    if ( isset($arr['v3privProtocol'] )) { $v3privProtocol=$arr['v3privProtocol'];} else { $v3privProtocol="v3privProtocol"; }
    if ( isset($arr['v3privPassword'] )) { $v3privPassword=$arr['v3privPassword'];} else { $v3privPassword="v3privPassword"; }
    if ( isset($arr['v3context'] ))      { $v3context          =$arr['v3context'];} else { $v3context="v3context"; }
    if ( isset($arr['v3engineId'] ))     { $v3engineId        =$arr['v3engineId'];} else { $v3engineId="v3engineId"; }
    if ( isset($arr['port'] ))           { $port                   =$arr['port'] ;} else { $port=161; }

    // Assume SNMP failed until proven otherwise
    $testResult="failure";
    // $finResult=array( "snmpEnable" => "false", "version" => "4", "community" => "none", "port" => "none");
    $finVer="none";
    $finComm="none";
    $finEnable="false";
    /*
       walk each version with each community to see if we can find
       any kind of SNMP response at all
       SNMP V3 is basically stubbed at this time.
    */
    foreach ($versions as $version) {
      if ( $testResult !== "failure" ) { break; }    // No matter what, if we have found our working values, STOP
      // echo "VERSION " . $version . "\n";
      foreach ($communities as $community) {
        if ( $testResult !== "failure" ) { break; }  // No matter what, if we have found our working values, STOP
        // echo "COMMUNITY " . $community . "\n";
        if ( $testResult == "failure" ) {
          $cmd='/usr/bin/php /opt/nmsApi/utilities/snmptest.php ' . $address . " " . $community . " " . $version;
          $testResult=shell_exec($cmd);
          $notes[] = $testResult . " Version " . $version . " community " . $community . " command " . $cmd;
//return $notes;
          // $testResult = testSnmp($version, $community,$hostname , $timeout, $retries);
          // echo "TEST RESULT " . $testResult . "\n";
          if ( $testResult == "failure" ) {
            $res=0;   // may be useful in the future
            // echo "failure version " . $version . " community " . $community . "\n";
          }
          else {
            $res=1;  // May be useful in the future
            // echo "success version " . $version . " community " . $community . "\n";
            $finVer=$version;
            $finComm=$community;
            $finEnable="true";
            break;
            break;
          }
        }
        else {
          $res=3;  // May be useful in the future
          // echo "Already found\n";
        }  // end if
      }  // end second foreach
    }  // end first foreach
    $finResult=array("snmpEnable" => $finEnable, "version" => $finVer , "community" => $finComm, "snmpPort" => $port);
    return $finResult;
  } // end FindDeviceSnmpSettings function

  public function CreateDiscoveredDevice($arr):array {
    // This is simply going to call the create device API
    // That will take care of logic and additional validation.
    // This is mainly for consistency?  Because I wanna?
    $this->db->prepare("SELECT * FROM Device WHERE hostname= :hostname AND address= :address");
    $this->db->bind('hostname', $arr['hostname']);
    $this->db->bind('address', $arr['address']);
    $data = $this->db->resultset();
    $row = $this->db->rowCount();
    $post = array();
    $post[]= "DeviceFolder="  .   $arr['DeviceFolder'];
    if ($row == 0 ) {
      // No existing folder found
      $post[] = "hostname="        . $arr['hostname'];
      $post[] = "address="         . $arr['address'];

      if ( ! empty($arr['productionState'])) {
        $post[] = "productionState=" . $arr['hostname'];
      }
      else {
        $post[] = "productionState=1";
      }
      $ch = curl_init('http://localhost:8002/device/create');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      header('Content-Type: text/html');
      $curlResult=curl_exec($ch);
      curl_close ($ch);
    }
    else {
      $curlResult="Device already exists with same Address.  Cannot create another";
    }
  }

  public function CreateDiscoveredDeviceFolder($arr): array {
    // Check if folder already exists.  Update Devices if
    // folder is found instead of a base insert
    $this->db->prepare("SELECT * FROM DeviceFolder WHERE DeviceFolder= :DeviceFolder");
    $this->db->bind('DeviceFolder', $arr['DeviceFolder']);
    $data = $this->db->resultset();
    $row = $this->db->rowCount();
    $post = array();
    $post[]= "DeviceFolder="  .   $arr['DeviceFolder'];
    if ($row == 0 ) {
      // No existing folder found
      $post[] = "Devices="   .       $arr['Devices'];
      $ch = curl_init('http://localhost:8002/deviceFolder/create');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      header('Content-Type: text/html');
      $curlResult=curl_exec($ch);
      curl_close ($ch);
    }
    else {
      $curlResult="DeviceFolder already exists.  Cannot create";
    }
    return $curlResult;
  }

  public function CreateDiscoveredDeviceProperties($arr): array {
    $DeviceId = $arr['id'];
    unset($arr['hostname']);
    unset($arr['address']);
    unset($arr['id']);
    $this->db->prepare("INSERT INTO DeviceProperties VALUES(NULL, :DeviceId, :Properties) ON DUPLICATE KEY UPDATE Properties= :Properties");
    $this->db->bind('DeviceId', $DeviceId);
    $this->db->bind('Properties', json_encode($arr, 1));
    $data = $this->db->resultset();
    return $arr;
  }


}  // End CLASS
