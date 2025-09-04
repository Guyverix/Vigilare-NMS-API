<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Maintenance;

use Database;
use App\Domain\Maintenance\Maintenance;
use App\Domain\Maintenance\MaintenanceNotFoundException;
use App\Domain\Maintenance\MaintenanceRepository;

class DatabaseMaintenanceRepository implements MaintenanceRepository {
  public $db = "";

  public function __construct() {
     $this->db = new Database();
  }

  public function setMaintenance($maintenanceRequest): array {
      if (empty($maintenanceRequest['groups']) || ! isset($maintenanceRequest['groups'])) { $maintenanceRequest['groups'] = ''; }
      if (empty($maintenanceRequest['application']) || ! isset($maintenanceRequest['application'])) { $maintenanceRequest['application'] = ''; }
      if (empty($maintenanceRequest['device']) || ! isset($maintenanceRequest['device'])) { $maintenanceRequest['device'] = ''; }
      if (empty($maintenanceRequest['component']) || ! isset($maintenanceRequest['component'])) { $maintenanceRequest['component'] = ''; }
      if (empty($maintenanceRequest['summary']) || ! isset($maintenanceRequest['summary'])) { $maintenanceRequest['summary'] = 'Generic maintenance event'; }
      if (empty($maintenanceRequest['startTime']) || ! isset($maintenanceRequest['startTime'])) { $maintenanceRequest['startTime'] = date('Y-m-d H:i:s'); }
      if (empty($maintenanceRequest['endTime']) || ! isset($maintenanceRequest['endTime'])) { $maintenanceRequest['endTime'] = date('Y-m-d H:i:s', strtotime('+1 days')); }
      $this->db->prepare("INSERT INTO event.maintenance (groups, application,device,component,start_time,end_time) VALUES(:groups, :application, :device, :component, :summary :startTime, :endTime)");
      $this->db->bind('groups', $maintenanceRequest['groups']);
      $this->db->bind('application', $maintenanceRequest['application']);
      $this->db->bind('device',$maintenanceRequest['device']);
      $this->db->bind('component', $maintenanceRequest['component']);
      $this->db->bind('summary', $maintenanceRequest['summary']);
      $this->db->bind('startTime', $maintenanceRequest['startTime']);
      $this->db->bind('endTime', $maintenanceRequest['endTime']);
      $this->db->execute();
      return ["maintenance set complete"];
  }

  public function endMaintenance($maintenanceRequest): array {
      $device=$maintenanceRequest['device'];
      $endTime=$maintenanceRequest['endTime'];
      $query="UPDATE event.maintenance SET end_time = :endTime WHERE device = :device AND end_time = '0000-00-00 00:00:00'";
      $this->db->prepare($query);
      $this->db->bind('endTime', $endTime);
      $this->db->bind('device', $maintenanceRequest['device']);
      return ["maintenance end complete"];
  }

  public function findMaintenanceDevice($maintenanceRequest): array {
      $device=$maintenanceRequest['device'];
      $this->db->prepare("SELECT * FROM event.maintenance WHERE device = :device");
      $this->db->bind('device', $device);
      $data = $this->db->resultset();
      return $data;
  }

  public function findMaintenanceComponent($maintenanceRequest): array {
      $component = $maintenanceRequest['component'];
      $this->db->prepare("SELECT * FROM event.maintenance WHERE component = :component");
      $this->db->bind('component', $component);
      $data = $this->db->resultset();
      return array_values($data);
  }

  public function findMaintenanceStart($maintenanceRequest): array {
      $startTime = $maintenanceRequest['startTime'];
      $direction = $maintenanceRequest['direction'];
      if ( $direction == "greater")  { $dir='>='; }
      elseif ( $direction == "less") { $dir='<='; }
      elseif ( $direction == "before") { $dir='<='; }
      elseif ( $direction == "after") { $dir='<='; }
      elseif ( $direction == "equal") { $dir='='; }
      else { $dir='='; }
      $maintenanceRequest['dir']=$dir;
      $query="SELECT * FROM event.maintenance where start_time " . $dir . " '" . $maintenanceRequest['startTime'] . "'";
      $this->db->prepare($query);
      $data = $this->db->resultset();
      return array_values($data);
  }

  public function findMaintenanceEnd($maintenanceRequest): array {
      $endTime = $maintenanceRequest['endTime'];
      $direction = $maintenanceRequest['direction'];
      if ( $direction == "greater")  { $dir='>='; }
      elseif ( $direction == "less") { $dir='<='; }
      elseif ( $direction == "before") { $dir='<='; }
      elseif ( $direction == "after") { $dir='<='; }
      elseif ( $direction == "equal") { $dir='='; }
      else { $dir = '='; }
      $query="SELECT * FROM event.maintenance WHERE end_time " . $dir . " '" . $maintenanceRequest['endTime'] . "'";
      $this->db->prepare($query);
      $data = $this->db->resultset();
      return array_values($data);
  }

  public function findAllMaintenance() {
      $query="SELECT * FROM event.maintenance WHERE endTime >= NOW()";
      $this->db->prepare($query);
      $data = $this->db->resultset();
      return $data;
  }

  public function findMaintenanceInvalid($maintenanceRequest): array {
      $result = ["Invalid API call received"];
      return $result ;
  }
}

