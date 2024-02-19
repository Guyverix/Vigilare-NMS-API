<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Reports;

use App\Domain\Reports\Reports;
use App\Domain\Reports\ReportsNotFoundException;
use App\Domain\Reports\ReportsRepository;
use Database;

/*
  Assuming people do not go crazy with reports, these
  should be fast in generation.  However once they start
  getting complex, this is going to grind the database
  down to a halt.  When additional reports are created that
  are slow, it will be a good time to create a read replica
  so that all reporting is done against that instead
  of a write database.  This will really only be needed
  in big installations, or when data munging gets more
  complex for the reports generated.

  Version 1 of this software is not natively going to
  have the code in place for a RR server.  Version 2
  likely will as an optional setting in the config.

  Current testing is against ~50 hosts, so dont see
  a need for building a RR, since it is obvious that
  it will be needed when counts get higher than about
  300-500 hosts.

  At that time, likely there would be multiple remote pollers
  as well as more complex reports created.
*/

class DatabaseReportsRepository implements ReportsRepository {
  public $db;


  // This include may or may not work.. still need to test
  public function __construct() {
    $this->db = new Database();
    if (file_exists( __DIR__ . "/custom_DatabaseReportsRepository.php")) {
      include_once __DIR__ . ("/custom_DatabaseReportsRepository.php");
    }
  }

  public function findActiveCustomerVisible() {
    $this->db->prepare("SELECT e.device, e.stateChange, e.eventSummary FROM event e LEFT JOIN  monitoringDevicePoller m on e.eventName=m.checkName WHERE m.visible='yes'");
    $data = $this->db->resultset();
    return $data;
  }

  public function findHistoryCustomerVisible($arr) {
    $date = new DateTime();
    if ( ! isset($arr['startEvent']) || empty($arr['startEvent'])) {
      $arr['startEvent'] = "-1 day";
    }
    $date->modify($arr['startEvent']);
    $reportDate = $date->format("Y-m-d H:i:s");
    $this->db->prepare("SELECT h.device, h.stateChange, h.eventSummary FROM history h LEFT JOIN  monitoringDevicePoller m on h.eventName=m.checkName WHERE m.visible='yes' AND h.startDate >= :startDate ");
    $this->db->bind('startDate', $reportDate);
    $data = $this->db->resultset();
    return $data;
  }

  public function findActiveSeverityCount() {
    $this->db->prepare("SELECT eventSeverity, count(*) FROM event GROUP BY eventSeverity ORDER BY eventSeverity DESC");
    $data = $this->db->resultset();
    return $data;
  }

  public function findHistorySeverityCount() {
    $this->db->prepare("SELECT eventSeverity, count(*) FROM history GROUP BY eventSeverity ORDER BY eventSeverity DESC");
    $data = $this->db->resultset();
    return $data;
  }
}
?>
