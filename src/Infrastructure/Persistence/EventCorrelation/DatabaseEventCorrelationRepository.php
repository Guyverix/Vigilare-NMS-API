<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventCorrelation;


  /*
    According to ChatGPT you cannot easily make a "use" clause within a Class block
    and need to adjust for that when using closures.  So the function will sit
    outside of the class.  Still got it working in the class as well, but meh, keep it.

    Function pulled from:
    https://stackoverflow.com/questions/64430971/generate-nested-tree-structure-or-hierarchy-based-on-parent-child-relationship-u
    https://pastebin.com/raw/1pAU6J5y (data for example above)
  */

  function generateTree($data) {
    // return $data[0];
    $arrChild = [];   // Store parent as key and its childs as array to quickly find out.
    foreach($data as $obj){
      $arrChild[$obj['parentId']][] = $obj['categoryId'];
      $data[$obj['categoryId']] = $obj;
    }
    $final = [];
    // return $data;
    //return $arrChild;
    // This is a closure function
    $setChild = function(&$array, $parents) use (&$setChild, $data, $arrChild) {
      // return $parents;
      foreach($parents as $parent){
        $temp = $data[$parent];
        // If key is set, that means given node has direct childs, so process them too.
        if(isset($arrChild[$parent])){
          $temp['children'] = [];
          $setChild($temp['children'], $arrChild[$parent]);
        }
        $array[] = $temp;
      }
    };
    // Empty key would represent nodes with parent as `null`
    $setChild($final, $arrChild['']);
    return $final;
  }

/*
  Normal configuration and logic begins here for Class
  definition

  the above function is an oddball :)
*/

use App\Domain\EventCorrelation\EventCorrelation;
use App\Domain\EventCorrelation\EventCorrelationNotFoundException;
use App\Domain\EventCorrelation\EventCorrelationRepository;
use Database;

class DatabaseEventCorrelationRepository implements EventCorrelationRepository {
  public $db = "";

  public function __construct() {
    $this->db = new Database();
  }

  private function generateTreeObj($data) {
    $arrChild = [];   // Store parent as key and its childs as array to quickly find out.
    foreach($data as $obj){
        $arrChild[$obj->parentId][] = $obj->categoryId;
        $data[$obj->categoryId] = $obj;
    }
    $final = [];
    $setChild = function(&$array, $parents) use (&$setChild, $data, $arrChild) {
        foreach($parents as $parent) {
            $temp = $data[$parent];
            // If key is set, that means given node has direct childs, so process them too.
            if(isset($arrChild[$parent])) {
                $temp->children = [];
                $setChild($temp->children, $arrChild[$parent]);
            }
            $array[] = $temp;
        }
    };
    // Empty key would represent nodes with parent as `null`
    $setChild($final, $arrChild['']);
    return $final;
  }

  private function searchRule($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  private function searchRuleAnd($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value AND :column2 :filter2 :value2");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $this->db->bind('column2', $arr['column2']);
    $this->db->bind('filter2', $arr['filter2']);
    $this->db->bind('value2', $arr['value2']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  private function searchRuleOr($arr) {
    $this->db->prepare("SELECT COUNT(*) as count FROM :table WHERE :column :filter :value OR :column2 :filter2 :value2");
    $this->db->bind('table', $arr['table']);
    $this->db->bind('column', $arr['column']);
    $this->db->bind('filter', $arr['filter']);
    $this->db->bind('value', $arr['value']);
    $this->db->bind('column2', $arr['column2']);
    $this->db->bind('filter2', $arr['filter2']);
    $this->db->bind('value2', $arr['value2']);
    $data = $this->db->resultset();
    return $data['count'];
  }

  public function createEceGroups($arr) {
    $this->db->prepare("INSERT INTO eceGroups VALUES('', :parentId, :categoryName, :associatedHost, :associatedCheck)");
    $this->db->bind('parentId', $arr['parentId']);
    $this->db->bind('categoryName', $arr['categoryName']);
    $this->db->bind('associatedHost', $arr['associatedHost']);
    $this->db->bind('associatedCheck', $arr['associatedCheck']);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eceGroups values.  Contact admin.', $this->db->error];
    }
    return "Created eceGroup value " . json_encode($arr,1);
  }

  public function createRule($arr) {
    $this->db->prepare("INSERT INTO eventCorrleationEngine VALUES('', :active, :appName , :appService, :appState, :serviceState, :serviceName, :appCorrelation, :eceSummary)");
    $this->db->bind('active', $arr['active']);
    $this->db->bind('appName', $arr['appName']);
    $this->db->bind('appService', $arr['appService']);
    $this->db->bind('appState', $arr['appState']);
    $this->db->bind('serviceState', $arr['serviceState']);
    $this->db->bind('serviceName', $arr['serviceName']);
    $this->db->bind('appCorrelation', $arr['appCorrelation']);
    $this->db->bind('eceSummary', $arr['eceSummary']);
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eventCorrelationEngine values.  Contact admin.', $this->db->error];
    }
    return "Event Corrleation Engine Rule " . $arr['id'] . " has been updated";
  }

  public function updateRule($arr) {
    $this->db->prepare("UPDATE eventCorrleationEngine SET active= :active, appName= :appName , appService= :appService, appState= :appState, serviceState= :serviceState, serviceName= :serviceName, appCorrleation= :appCorrelation WHERE id= :id");
    $this->db->bind('active', $arr['active']);
    $this->db->bind('appName', $arr['appName']);
    $this->db->bind('appService', $arr['appService']);
    $this->db->bind('appState', $arr['appState']);
    $this->db->bind('serviceState', $arr['serviceState']);
    $this->db->bind('serviceName', $arr['serviceName']);
    $this->db->bind('appCorrelation', $arr['appCorrelation']);
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eventCorrelationEngine values.  Contact admin.', $this->db->error];
    }
    return "Event Corrleation Engine Rule " . $arr['id'] . " has been updated";
  }

  public function deleteRule($id) {
    $this->db->prepare("DELETE FROM eventCorrleationEngine WHERE id= :id");
    $this->db->bind('id', $id);
    $this->db->execute();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eventCorrelationEngine values.  Contact admin.', $this->db->error];
    }
    return "Event Correlation Egnine Rule " . $id . " has been deleted";
  }

  public function findRule() {
    $this->db->prepare("SELECT * FROM eventCorrelationEngine ORDER BY id ASC");
    $data = $this->db->resultset();
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eventCorrelationEngine values.  Contact admin.', $this->db->error];
    }
    return array_values($data);
  }

  public function familyRule() {
    $this->db->prepare("SELECT * FROM eceGroups");
    $data = $this->db->resultset();
    // $data = json_decode(json_encode($data,1), true);
    // $result = generateTree($data);
    $result = self::generateTreeObj($data);
    if (! empty($this->db->error)) {
      return ['FAILURE - Unable to set eventCorrelationEngine values.  Contact admin.', $this->db->error];
    }
    return $result;
  }
}
