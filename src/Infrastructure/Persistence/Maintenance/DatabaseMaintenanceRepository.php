<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Maintenance;

use PDO;
use App\Domain\Maintenance\Maintenance;
use App\Domain\Maintenance\MaintenanceNotFoundException;
use App\Domain\Maintenance\MaintenanceRepository;

class DatabaseMaintenanceRepository implements MaintenanceRepository {
  private $creds;
  private $server;
  private $user;
  private $pass;
  private $options;
  private $event;

  public function __construct() {
    /* PDO5.php is main database connection */
    include (__DIR__.'/../../../Database/PDO5.php');
    $a = creds();
    $this->creds = $a;
    $this->server = $a["server"];
    $this->user = $a["user"];
    $this->pass = $a["pass"];
  }


  public function setMaintenance($maintenanceRequest): array {
      $db = new PDO($this->server, $this->user, $this->pass);
      $sth = $db->prepare("INSERT INTO event.maintenance (device,component,start_time,end_time) VALUES(?,?,?,?)");
      $sth->execute([$maintenanceRequest['device'], $maintenanceRequest['component'], $maintenanceRequest['startTime'], $maintenanceRequest['endTime']]);
      return ["maintenance set complete"];
  }

  public function endMaintenance($maintenanceRequest): array {
      $device=$maintenanceRequest['device'];
      $endTime=$maintenanceRequest['endTime'];
      $db = new PDO($this->server, $this->user, $this->pass);
      $query="UPDATE event.maintenance SET end_time = ? WHERE device = ? AND end_time = '0000-00-00 00:00:00'";
      $sth = $db->prepare($query);
      $sth->execute([$maintenanceRequest['endTime'], $maintenanceRequest['device']]);
      return ["maintenance end complete"];
  }

  public function findMaintenanceDevice($maintenanceRequest): array {
      $device=$maintenanceRequest['device'];
      $db = new PDO($this->server, $this->user, $this->pass);
      $sth = $db->prepare("SELECT * FROM event.maintenance WHERE device = ?");
      $sth->execute([$device]);
      $data = $sth->fetchAll(PDO::FETCH_ASSOC);
      return array_values($data);
  }

  public function findMaintenanceComponent($maintenanceRequest): array {
      $component = $maintenanceRequest['component'];
      $db = new PDO($this->server, $this->user, $this->pass);
      $sth = $db->prepare("SELECT * FROM event.maintenance WHERE component = ?");
      $sth->execute([$component]);
      $data = $sth->fetchAll(PDO::FETCH_ASSOC);
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
      $db = new PDO($this->server, $this->user, $this->pass);
      $query="SELECT * FROM event.maintenance where start_time " . $dir . " '" . $maintenanceRequest['startTime'] . "'";
      $sth = $db->prepare($query);
      $sth->execute();
      $data = $sth->fetchAll(PDO::FETCH_ASSOC);
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
      else { $dir='='; }
      $db = new PDO($this->server, $this->user, $this->pass);
      $query="SELECT * FROM event.maintenance WHERE end_time " . $dir . " '" . $maintenanceRequest['endTime'] . "'";
      $sth = $db->prepare($query);
      $sth->execute();
      $data = $sth->fetchAll(PDO::FETCH_ASSOC);
      return array_values($data);
  }

  public function findMaintenanceInvalid($maintenanceRequest): array {
    $result=["Invalid API call received"];
    return $result ;
  }
}

