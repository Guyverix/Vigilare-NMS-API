<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Reporting;

use App\Domain\Reporting\Reporting;
use App\Domain\Reporting\ReportingNotFoundException;
use App\Domain\Reporting\ReportingRepository;

// Database defined in ReportingRepository
use Database;

class DatabaseReportingRepository implements ReportingRepository {
  public $db = "";

  // This will only ever be called from within this class
  private function getFiles($path) {
    if (is_dir($path)) {
      $res = array();
      foreach (array_filter(glob($path ."*.php"), 'is_file') as $file) {
        array_push($res, str_replace($path, "", $file));
      }
      return $res;
    }
    return false;
  }

  public function __construct() {
    $this->db = new Database();
  }

/*
  // Create new template on filesystem?  Beginning to think this is not a good idea
  public function createTemplate($arr) {
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";
  }

  // update template values on filesystem?  This also is not a great idea
  public function updateTemplate($arr) {
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";

  }

  // Display the template itself in GUI?  This is not a good idea
  public function viewTemplate($arr) {
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";

  }

  // Remove a template from filesystem? Really rethinking this option
  public function deleteTemplate($arr) {
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";
    if (file_exists( __DIR__ . $fileName)) {
      unlink(__DIR__ . $fileName);
      return 'template file removed';
    }
    else {
      return 'No template file existed for removal';
    }
  }
*/
  // return list of templates from filesystem
  public function searchTemplate() {
    $templateList = $this->getFiles(__DIR__ . '/../../../../templates/reporting/');
    $returnList = array();
    foreach ($templateList as $singleTemplate) {
      $usedVars = shell_exec("php " . __DIR__ . '/../../../../templates/reporting/' . $singleTemplate);
      $cleanedName = preg_replace('/.php/','',$singleTemplate);
      $returnList[] = [ 'template' => $cleanedName , 'usedVars' => $usedVars ];
    }
    return $returnList;
  }

  // Return list of all completed reports
  public function searchReport() {
    $this->db->prepare("SELECT id, reportName, reportDate, status FROM Reporting");
    $data = $this->db->resultset();
    return $data;
  }

  // View a specific completed report
  public function viewReport($arr) {
    $this->db->prepare("SELECT * FROM Reporting WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $data = $this->db->resultset();
    $data = json_decode(json_encode($data,1), true);  // convert from obj to array
    $data2['reportResult'] = json_decode($data[0]['reportResult'], true);  // convert from json to array
    $data2['filterValues'] = json_decode($data[0]['filterValues'], true);  // convert from json to array
    return $data2;
  }

  // Nuke a completed report
  public function deleteReport($arr) {
    $this->db->prepare("DELETE from Reporting WHERE id= :id");
    $this->db->bind('id', $arr['id']);
    $this->db->execute();
    return 'Report ' . $arr['id'] . ' has been purged from database';
  }

  public function findReports() {
    $this->db->prepare("SELECT id, reportName, reportDate, status FROM Reporting");
    $data = $this->db->resultset();
    return json_decode($data,true);
  }

  // This is a REQUEST report only
  public function createReport($arr) {
    // Confirm if we even CAN run a report
    $filterArgs = json_encode($arr,1);
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";
    if (file_exists( __DIR__ . $fileName)) {
      $this->db->prepare("INSERT INTO Reporting VALUES('', :reportName , '' , now(), :filterArgs, 'pending')");
      $this->db->bind('reportName', $arr['template']);
      $this->db->bind('filterArgs', $filterArgs);
      $this->db->execute();
      return "Requested report to be generated";
    }
    else {
      return "Invalid report name " . $arr['template'] . " template does not exist!";
    }
  }

  public function findPreviousReports() {
    // likely will never be used, as we can see them from the findReports
  }

  public function findPending() {
    $this->db->prepare("SELECT id FROM Reporting WHERE status='pending'");
    $data = $this->db->resultset();
    return $data;
  }

  public function runPending($arr) {
    $id = $arr['id'];
    $this->db->prepare("SELECT filterValues FROM Reporting WHERE id= :id");
    $this->db->bind('id', $id);
    $initialValues = $this->db->resultset();
    $initialValues = json_decode(json_encode($initialValues,1), true);
    $arr += json_decode($initialValues[0]['filterValues'], true);  // yes, we are appending to the existing single array value  This should give a chance at override
    if ( ! empty($arr)) {
      $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";
      if (file_exists( __DIR__ . $fileName)) {
        try {
          include (__DIR__ . $fileName);
          if ( ! isset($query)) {
            return "Failed to set manditory paramters to run report";
          }
          $data = $this->db->resultset();
          $this->db->prepare("UPDATE Reporting SET reportResult= :data, status='complete' WHERE id= :id2");
          $this->db->bind('id2', $id);
          $this->db->bind('data', json_encode($data,1));
          $this->db->execute();
          return "Report run and saved successfully";
        }
        // https://stackoverflow.com/questions/15461611/php-try-catch-not-catching-all-exceptions
        catch (Exception|TypeError $e) {
          // Update the report table that the report failed
          $this->db->prepare("UPDATE Reporting SET status='failure' WHERE id= :id3 ");
          $this->db->bind('id3', $id);
          $this->db->execute();
          return "error in either template values or file itself";
        }
      }
      else {
        return "File does not exist";
      }
    }
    else {
      return "Unable to pull values saved for the pending run";
    }
  }


  // Run new report NOW
  public function runReport($arr) {
    // Retrieve vars from filesystem for use
    $fileName = '/../../../../templates/reporting/' . $arr['template'] . ".php";
    if (file_exists( __DIR__ . $fileName)) {
      try {
        include (__DIR__ . $fileName);
        if ( ! isset($query)) {
          return "Failed to set manditory paramters to run report";
        }
        $data = $this->db->resultset();
        $filterArgs = json_encode($arr,1);
        $this->db->prepare("INSERT INTO Reporting VALUES('', :reportName , :data , now(), :filterArgs, 'complete')");
        $this->db->bind('reportName', $arr['template']);
        $this->db->bind('filterArgs', $filterArgs);
        $this->db->bind('data', json_encode($data,1));
        $this->db->execute();
        return "Report run and saved successfully";
      }
      // https://stackoverflow.com/questions/15461611/php-try-catch-not-catching-all-exceptions
      catch (Exception|TypeError $e) {
        // Update the report table that the report failed
        $this->db->prepare("INSERT INTO Reporting VALUES('', :reportName , '' , now(), 'failure')");
        $this->db->bind('reportName', $arr['template']);
        $this->db->execute();
        return "error in either template values or file itself";
      }
    }
    else {
     return 'Template file does not exist';
    }
  }  // end function
} // end class
?>
