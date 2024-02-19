<?php
declare(strict_types=1);


namespace App\Infrastructure\Persistence\SmartPoller;

/* Called from Domain\Poller\PollerRepository */

use App\Domain\Poller\Poller;
use App\Domain\Poller\PollerNotFoundException;
use App\Domain\Poller\PollerRepository;
use Database;

class DatabaseSmartPollerRepository implements PollerRepository {
  public $db;


  public function __construct() {
    $this->db = new Database();
  }

  private $path = __DIR__ . '/../../../../daemon/';

  public function statusPid($poller) {
    return "empty function ". $poller . "\n";
  }

  public function pollerHeartbeat() {
    $this->db->prepare("SELECT * FROM heartbeat ORDER BY device");
    $this->db->execute();
    $results = $this->db->resultset();
    return array_values($results);
  }

  public function pollerList() {
    $this->db->prepare("SELECT type, iteration FROM monitoringDevicePoller WHERE type in (select distinct(type) from monitoringDevicePoller) group by type, iteration");
    $this->db->execute();
    $results = $this->db->resultset();
    $results2 = json_decode(json_encode($results),true);
    $resultFiltered=array();
    foreach ($results2 as $list) {
      if ( $list['type'] == 'get') {
        if ( ! array_key_exists('snmp', $resultFiltered)) { $list['type'] = 'snmp'; }
      }
      if ( $list['type'] == 'walk') {
        if ( ! array_key_exists('snmp', $resultFiltered)) { $list['type'] = 'snmp'; }
      }
      /* Loop through the damn filtered list looking for dupes in SNMP due to get and walk getting converted to string SNMP */

      if ( $list['type'] != 'disable' && ! array_key_exists('snmp',$resultFiltered)) {
        $resultFiltered[][$list['type']] = $list['iteration'];
      }
    }
    $resultFiltered2 = array_values($resultFiltered);
    $resultfiltered2 = array_multisort($resultFiltered2);
    $resultFiltered2 = array_unique($resultFiltered2, SORT_REGULAR);
    $resultFiltered2 = array_filter($resultFiltered2);
    return $resultFiltered2;
  }


  public function pollerIteration($poller) {
    switch ($poller) {
      case 'snmp':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='walk' OR type='get' ");
        break;
      case 'ping':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='ping'");
        break;
      case 'alive':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='alive'");
        break;
      case 'nrpe':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='nrpe'");
        break;
      case 'shell':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='shell'");
        break;
      case 'curl':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='curl'");
        break;
      case 'housekeeping':
        $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type='housekeeping'");
        /* We can fake the housekeeping iteration if needed
          $this->db->prepare("select 60 as iteration;");
        */
        break;
      default:
       // Have a way to support random of pollers.  This WILL give goofy responses if there is crap in the db
       $this->db->prepare("SELECT distinct(iteration) FROM monitoringDevicePoller WHERE type= :poller");
       $this->db->bind('poller', $poller);
       break;
    }
    $this->db->execute();
    $results = $this->db->resultset();
    return array_values($results);
  }

  // Not a database query, but might as well have it all in one spot
  public function pollerStatus($poller) {
    $pollerType    = $poller;
    $iterationList = $this->pollerIteration($pollerType);
    $iterationList = json_encode($iterationList);
    $iterationList = json_decode($iterationList, true);
    $results = array();
    foreach ($iterationList as $iteration) {
      if ( $poller == "housekeeping") {
        $status = file_exists($this->path . '/housekeepingPoller/housekeepingPoller.' . $iteration['iteration'] . '.pid') ;
        $pDest="/housekeepingPoller/";
      }
      else {
        $status = file_exists($this->path . '/Poller/' . strtolower($poller) . 'Poller.' . $iteration['iteration'] . '.pid') ;
        $pDest="/Poller/";
      }
      if(! $status) {
        $result ="Iteration ". $iteration['iteration'] . " no pidfile found";
      }
      else {
        if ( $poller == "housekeeping") {
          $res=file_get_contents($this->path . '/housekeepingPoller/housekeepingPoller.' . $iteration['iteration'] . '.pid');
        }
        else {
          $res=file_get_contents($this->path . $pDest . strtolower($poller) . 'Poller.' . $iteration['iteration'] . '.pid');
        }
        if (empty($res)) {
          $result = "Iteration " . $iteration['iteration'] . " pidfile found.  No pid recorded as running.";
        }
        elseif (! empty($res)) {
          if (file_exists("/proc/$res")) {
            $result ="running pid for " . ucfirst($poller) . "Poller iteration " . $iteration['iteration'] . " is " . $res;
          }
          else {
            $result = "pidfile is stale.  There is no known running process for ". ucfirst($poller) . "Poller.php iteration " . $iteration['iteration'];
          }
        }
        else {
          $result ="running pid for " . ucfirst($poller) . "Poller iteration " . $iteration['iteration'] . " is " . $res;
        }
      }
      $results[] = $result;
    }
    return array_values($results);
  }

  public function pollerRun($poller) {
    $pollerType    = $poller;
    $iterationList = $this->pollerIteration($pollerType);
    $iterationList = json_encode($iterationList);
    $iterationList = json_decode($iterationList, true);
    foreach ($iterationList as $iteration) {
      if ( $poller == "housekeeping" ) {
        $cmd='nohup nice -n 10 /usr/bin/php ./smartHousekeepingPoller.php -i ' . $iteration['iteration'] . ' -s start > /dev/null & printf "%u" $!';
        chdir($this->path . 'housekeepingPoller/');
      }
      else {
        $cmd='nohup nice -n 10 /usr/bin/php ./genericPoller.php -i ' . $iteration['iteration'] . ' -t '. ucfirst($poller) . ' -s start > /dev/null & printf "%u" $!';
        chdir($this->path . 'Poller/');
      }
      $proc=shell_exec($cmd);
      $status[]="Iteration " . $iteration['iteration'] . " poller started with pid: $proc";
    }
    return array_values($status);
  }

  public function pollerKill($poller) {
    $pollerType    = $poller;
    $iterationList = $this->pollerIteration($pollerType);
    $iterationList = json_encode($iterationList);
    $iterationList = json_decode($iterationList, true);
    foreach ($iterationList as $iteration) {
      if ( $poller == "housekeeping") {
        $cmd='/usr/bin/php ./smartHousekeepingPoller.php -i ' . $iteration['iteration'] . ' -s stop';
        chdir($this->path . 'housekeepingPoller/');
      }
      else {
        $cmd='/usr/bin/php ./genericPoller.php -i ' . $iteration['iteration']  . ' -t '. strtolower($poller) . ' -s stop';
        chdir($this->path . 'Poller/');
      }
      $proc=shell_exec($cmd);
      $status[]="Iteration " . $iteration['iteration'] . " " . $poller.  " poller stopped";
    }
    return array_values($status);
  }

}
