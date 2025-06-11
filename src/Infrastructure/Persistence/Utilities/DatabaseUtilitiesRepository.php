<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Utilities;

use App\Domain\Utilities\Utilities;
use App\Domain\Utilities\UtilitiesNotFoundException;
use App\Domain\Utilities\UtilitiesRepository;
use Database;

class DatabaseUtilitiesRepository implements UtilitiesRepository {

  public function __construct() {
    $this->db = new Database();
  }

  public function FindAddressDevice($arr) {
    $this->db->prepare("SELECT * FROM Device WHERE address= :address");
    $this->db->bind('address', $arr['address']);
    $data = $this->db->resultset();
    $row = $this->db->rowCount();
    if ($row == 0 ) {
      $response = "unknown";
    }
    else {
      $response = json_encode($data,1);
    }
    return $response;
  }

  public function FindHostnameDevice($arr) {
    $this->db->prepare("SELECT * FROM Device WHERE hostname= :hostname");
    $this->db->bind('hostname', $arr['hostname']);
    $data = $this->db->resultset();
    $row = $this->db->rowCount();
    if ($row == 0 ) {
      $response = "unknown";
    }
    else {
      $response = json_encode($data,1);
    }
    return $response;
  }

  public function CheckIfKnownFromScan($arr) {
    // We got results from a network scan.  Lets check and see
    // if we already know about the IP address.  I think this
    // should be quicker than going the other direction..
    if ( empty($arr['hostname']) || empty($arr['address']) ){
      $response = "need both hostname and address";
    }
    else {
      $this->db->prepare("SELECT * FROM Device WHERE address= :address AND hostname= :hostname");
      $this->db->bind('address', $arr['address']);
      $this->db->bind('hostname', $arr['hostname']);
      $data = $this->db->resultset();
      $row = $this->db->rowCount();
      if ($row == 0 ) {
        $response = "unknown";
      }
      else {
        $response = "known";
      }
    }
    return $response;
  }

  public function GetAllKnownAddresses() {
    $this->db->prepare("SELECT address FROM Device");
    $data = $this->db->resultset();
    return $data;
  }

  public function GetAllKnownHostnames() {
    $this->db->prepare("SELECT hostname FROM Device");
    $data = $this->db->resultset();
    return $data;
  }

/*
   This is really not the best spot to store these since they are not db related
   but it will be good enough until I find a better spot
*/

  public function GetIpAddresses($arr) {
    // Create an array of IP addresses from the CIDR notation
    // Expected arg MUST be X.X.X.X/Y from array
    $cidr=$arr['cidr'];
    @list($ip, $len) = explode('/', $cidr);
    if (($min = ip2long($ip)) !== false) {
      $max = ($min | (1<<(32-$len))-1);
      for ($i = $min; $i < $max; $i++)
        $addresses[] = long2ip($i);
    }
    return $addresses;
  }

  public function CheckIfKnownAddressesInRange($arr) {
    // Find out if an IP address is within a given CIDR range
    //  $checkip = "8.8.8.154";
    //  $range = "8.8.8.0/24";
    $checkIp = $arr['checkIp'];
    $range = $arr['range'];
    @list($ip, $len) = explode('/', $range);
    if (($min = ip2long($ip)) !== false && !is_null($len)) {
      $clong = ip2long($checkIp);
      $max = ($min | (1<<(32-$len))-1);
      if ($clong > $min && $clong < $max) {
        $result="valid";
      }
      else {
        $result="invalid";
      }
    }
    return $result;
  }

  public function IpInNetwork($arr) {
    // function found at http://www.php.net/ip2long
    // returns true if in subnet, false if not
    $netMask=$arr['netMask'];
    $address = $arr['address'];
    $netAddr = $arr['netAddress'];
    if($netMask <= 0){
      return false;
    }
    $ipBinaryString = sprintf("%032b",ip2long($address));
    $netBinaryString = sprintf("%032b",ip2long($netAddress));
    return (substr_compare($ipBinaryString, $netBinaryString, 0, $netMask) === 0);
  }

  public function NmapPingScan($arr) {
    // This untested function will take a ping scan of a SUBNET, and return an array of results
    // where it can ping the tested IP address.

    //  $returns=array(exec("nmap -sP $subnet | grep 'Nmap scan report for' | sed 's/.*ort for //g' "));
    $subnet=$arr['subnet'];
    $sh_command="/usr/bin/nmap -sP " . $subnet . " | grep 'Nmap scan report for' | sed 's/.*ort for //g' ";
    exec("$sh_command", $nmapResult, $returns);
    return $nmapResult;
  }

  public function NmapDeviceOpenPorts($arr) {
    $address=$arr['address'];
    $sh_command="nmap " . $address . " -p 1-1024 -sT --open -oG - | grep 'Ports:' | sed 's/Ignored.*//' | sed 's/.*.Ports:\ //' ";
    exec("$sh_command", $nmapResult, $returns);
    $openPorts = explode (',', $nmapResult);
    return $openPorts;
  }

  public function ping($arr) {
    // Use caution, only a single ping is given
    // this may not be accurate enough in unstable
    // networks or WiFi.
    // This is only going to check if alive, not any
    // perf metric is returned.
    if ( ! empty($arr['hostname'])) {
      $host=$arr['hostname'];
    }
    elseif (! empty($arr['address'])) {
      $host=$arr['address'];
    }
    else {
      return False;
    }
    // Cant get much simpler than a basic ping check
    exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
    return $rval === 0;
  }

  public function FindIpAddress($arr) {
    // This is simple, we will likely need
    // support for CNAME and others as well
    // since those can be added into DeviceProperties for
    // VHOST as well as things like ELB
    $result=dns_get_record($arr['device'], DNS_A);
    return array_values($result);
  }

  public function FindIpAddressDigShort($arr) {
    if ( !empty($arr['hostname'])) {
      $query=$arr['hostname'];
    }
    if (! empty($arr['address'])) {
      $query=$arr['address'];
    }

    if ( !empty($query)) {
      $sh_command="dig +short " . $query;
      exec("$sh_command", $digShortResult, $returns);
    }
    else {
      $digShortResult="";
    }
    return $digShortResult;
  }

  public function FindIpAddressDigTrace($arr) {
    if ( !empty($arr['hostname'])) {
      $query=$arr['hostname'];
    }
    if (!empty($arr['address'])) {
      $query=$arr['address'];
    }

    if ( !empty($query)) {
      $sh_command= "dig +trace " . $query;
      exec("$sh_command", $digShortResult, $returns);
    }
    else {
      $digShortResult="";
    }
    return $digShortResult;
  }

  public function FindIpAddressDigAny($arr) {
    if ( !empty($arr['hostname'])) {
      $query=$arr['hostname'];
    }
    if (! empty($arr['address'])) {
      $query=$arr['address'];
    }

    if ( !empty($query)) {
      $sh_command="dig " . $query . " ANY";
      exec("$sh_command", $digShortResult, $returns);
    }
    else {
      $digShortResult="";
    }
    return $digShortResult;
  }
}  // end class call
?>
