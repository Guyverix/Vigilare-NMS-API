<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Host;

use App\Domain\Host\Host;
use App\Domain\Host\HostNotFoundException;
use App\Domain\Host\HostRepository;


/*
  Ignore the damn dependency injection as it is a PITA
  just get the database creds in place so we can query
*/
require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseHostRepository implements HostRepository {
  private $oid;
  private $display_name;
  private $severity;
  private $pre_processing;
  private $type;
  private $parent_of;
  private $child_of;
  private $age_out;
  private $post_processing;
  public $db="";

/*
  // Unused constructor. We really dont need this
  public function __construct($array) {
    $this->oid = $array['oid'];
    $this->display_name = $array['display_name'];
    $this->severity = $array['severity'];
    $this->pre_processing = $array['pre_processing'];
    $this->type = $array['type'];
    $this->parent_of = $array['parent_of'];
    $this->child_of = $array['child_of'];
    $this->age_out = $array['age_out'];
    $this->post_processing = $array['post_processing'];
  }
*/

  public function __construct() {
    $this->db = new Database();
  }

  public function findAllHost() {
    $this->db->prepare("SELECT * FROM host");
    $data = $this->db->resultset();
    return $data;
  }

  public function findHost($array) {
    $this->db->prepare("SELECT * FROM host h WHERE hostname = :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findAddress($array) {
    $this->db->prepare("SELECT * FROM host WHERE address = :address");
    $this->db->bind('address', $array['address']);
    $data = $this->db->resultset();
    return $data;
  }

  public function createHost($array) {
    $this->db->prepare("INSERT INTO host VALUES(NULL, :hostname, :address, NOW(), :monitor");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('monitor', $array['monitor']);
    $this->db->execute();
    $this->db->prepare("SELECT * FROM host WHERE hostname= :hostname AND address= :address AND monitor= :monitor");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('monitor', $array['monitor']);
    $data = $this->db->resultset();
    return $data;
  }

  public function updateHost($array) {
    $this->db->prepare("UPDATE host SET hostname= :hostname, address= :address, monitor= :monitor WHERE id= :id");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->bind('monitor', $array['monitor']);
    $this->db->bind('id', $array['id']);
    //$this->db->execute();
    $this->db->resultset();
    $this->db->prepare("SELECT * FROM host WHERE id= :id");
    $this->db->bind('id', $array['id']);
    $data = $this->db->resultset();
    return $data;
  }

  public function updateEvents($array) {
    $this->db->prepare("UPDATE event SET device=:hostname WHERE device= :address");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->bind('address', $array['address']);
    $this->db->execute();
    $this->db->prepare("SELECT count(*) FROM event WHERE device= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function deleteHost($array) {
    $this->db->prepare("DELETE FROM host WHERE id= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $this->db->execute();
    return "Hostname match for Host deletion";
  }

  public function deleteHostId($array) {
    $this->db->prepare("DELETE FROM host WHERE id= :id");
    $this->db->bind('id', $array['id']);
    $this->db->execute();
    return "Deleted host id " . $array['id'] . " Host deletion complete.";
  }

  public function findAttribute($array) {
    $this->db->prepare("SELECT component, name, value FROM hostAttribute WHERE hostname= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

  public function findPerformance($array) {
    $this->db->prepare("SELECT checkName, date, value FROM performance WHERE hostname= :hostname");
    $this->db->bind('hostname', $array['hostname']);
    $data = $this->db->resultset();
    return $data;
  }

} // end class

