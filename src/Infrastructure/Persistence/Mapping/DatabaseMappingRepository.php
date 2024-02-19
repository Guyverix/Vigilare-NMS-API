<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mapping;

use App\Domain\Mapping\Mapping;
use App\Domain\Mapping\MappingNotFoundException;
use App\Domain\Mapping\MappingRepository;


/*
  Ignore the damn dependency injection as it is a PITA
  just get the database creds in place so we can query
*/
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
  public $db;

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

  public function createMapping($array) {
    $this->db->prepare("INSERT INTO trapEventMap (oid, display_name, severity, pre_processing, type, parent_of, child_of, age_out, post_processing) VALUES(:oid, :display_name, :severity, :pre_processing, :type, :parent_of, :child_of, :age_out, :post_processing)");
    //    $sth->db->bind($array['oid'], $array['display_name'], $array['severity'], $array['pre_processing'], $array['type'], $array['parent_of'], $array['child_of'], $array['age_out'], $array['post_processing']);
    $this->db->bind('oid', $array['oid']);
    $this->db->bind('display_name', $array['display_name']);
    $this->db->bind('severity', $array['severity']);
    $this->db->bind('pre_processing', $array['pre_processing']);
    $this->db->bind('type', $array['type']);
    $this->db->bind('parent_of', $array['parent_of']);
    $this->db->bind('child_of', $array['child_of']);
    $this->db->bind('age_out', $array['age_out']);
    $this->db->bind('post_processing', $array['post_processing']);
//    $this->db->execute();
    $data = $this->db->resultset();
    return "Insert complete for " . $array['oid'];
//    return $this->db->stmt ;
  }

  public function updateMapping($array) {
    $this->db->prepare("UPDATE trapEventMap SET display_name= :display_name, severity= :severity, pre_processing= :pre_processing, type= :type, parent_of= :parent_of, child_of= :child_of, age_out= :age_out, post_processing= :post_processing WHERE oid = :oid");
    $this->db->bind('oid', $array['oid']);
    $this->db->bind('display_name', $array['display_name']);
    $this->db->bind('severity', $array['severity']);
    $this->db->bind('pre_processing', $array['pre_processing']);
    $this->db->bind('type', $array['type']);
    $this->db->bind('parent_of', $array['parent_of']);
    $this->db->bind('child_of', $array['child_of']);
    $this->db->bind('age_out', $array['age_out']);
    $this->db->bind('post_processing', $array['post_processing']);
    $this->db->execute();
    $data = $this->db->resultset();
    return "Update complete for " . $array['oid'];
  }

  public function deleteMapping($array) {
    $this->db->prepare("DELETE FROM trapEventMap WHERE oid= :oid");
    $this->db->bind('oid' , $array['oid']);
    $this->db->execute();
    $data = $this->db->resultset();
    return "Delete complete for " . $array['oid'];
  }

  public function findAllOid() {
    $this->db->prepare("SELECT * FROM trapEventMap");
    $this->db->execute();
    $data = $this->db->resultset();
    return $data;
  }

  public function findOid($array) {
    $this->db->prepare("SELECT * FROM trapEventMap WHERE oid= :oid");
    $this->db->bind('oid', $array['oid']);
    $this->db->execute();
    $data = $this->db->resultset();
    return $data;
  }

  // This will return * unmapped if we do not find an oid  might not be needed at all however
  public function findOidFull($array) {
    $this->db->prepare("SELECT * from trapEventMap WHERE oid= :oid OR oid='*' LIMIT 1");
    $this->db->bind('oid', $array['oid']);
    $this->db->execute();
    $data = $this->db->resultset();
    return $data;
  }


} // end class

