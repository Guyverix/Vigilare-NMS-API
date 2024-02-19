<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\NameMap;

use App\Domain\NameMap\NameMap;
use App\Domain\NameMap\NameMapNotFoundException;
use App\Domain\NameMap\NameMapRepository;

require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseMappingRepository implements MappingRepository {
  private $oid;
  private $display_name;
  private $severity;
  private $pre_processing;
  private $type;
  private $parent_of;
  private $child_of;
  private $age_out;
  private $post_processing;


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

  public function setNameMap($nameRequest): array {
      $this->db->prepare("INSERT INTO oidNameMap VALUES( :oid, :name)");
      $this->db->bind('oid',$nameRequest['oid']);
      $this->db->bind('name',$nameRequest['name']);
      $data = $this->db->resultset();
      $data += ["Insert complete"];
      return array_values($data);
  }

  public function findNameMap($nameRequest): array {
      if ( isset($nameRequest['oid']) && isset($nameRequest['name'])) {
        $this->db->prepare("SELECT * FROM oidNameMap WHERE oid= :oid OR name= :name");
        $this->db->bind('oid',$nameRequest['oid']);
        $this->db->bind('name',$nameRequest['name']);
      }
      elseif ( isset($nameRequest['oid'])) {
        $this->db->prepare("SELECT * FROM oidNameMap WHERE oid= :oid");
        $this->db->bind('oid',$nameRequest['oid']);
      }
      elseif (isset($nameRequest['name'])) {
        $this->db->prepare("SELECT * FROM oidNameMap WHERE name= :name");
        $this->db->bind('name',$nameRequest['name']);
      }
      else {
        // maybe roll up findAll to this catchall?
        $this->db->prepare("SELECT * FROM oidNameMap");
      }
      $data = $this->db->resultset();
      return array_values($data);
  }

  public function findAllNameMap() {
      $this->db->prepare("SELECT * FROM oidNameMap");
      $data = $this->db->resultset();
      return array_values($data);
  }

  public function updateNameMap($nameRequest): array {
      $this->db->prepare("UPDATE oidNameMap SET name= :name WHERE oid= :oid");
      $this->db->bind('oid',$nameRequest['oid']);
      $this->db->bind('name',$nameRequest['name']);
      $data = $this->db->resultset();
      $data += ["Update complete"];
      return array_values($data);
  }

  public function deleteNameMap($nameRequest): array {
      $db = new PDO($this->server, $this->user, $this->pass);
      $this->db->prepare("DELETE FROM event.oidNameMap WHERE oid= :oid'] AND name= :name");
      $this->db->bind('oid',$nameRequest['oid']);
      $this->db->bind('name',$nameRequest['name']);
      $data = $this->db->resultset();
      $data += ["Removed any mappings that matched"];
      return array_values($data);
  }

  public function invalidNameMap($nameRequest): array {
    $result=["Invalid API call received"];
    return $result ;
  }
}
