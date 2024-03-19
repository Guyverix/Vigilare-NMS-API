<?php
declare(strict_types=1);

namespace App\Application\Actions\MonitoringPoller;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\MonitoringPoller\MonitoringPollerAction;
use App\Domain\MonitoringPoller\MonitoringPollerRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageMonitoringPollerAction extends MonitoringPollerAction {
  protected function action(): Response {
    $jobType=["isAlive", "savePerformance", "deletePerformance", "heartbeat", "hostname", "hostgroup", "walk", "get", "snmp", "nrpe", "ping", "housekeeping", "disable", "all", "alive", "checkName", "shell"]; // sanity check that we only are doing what we expect here

    // How to check if resolveArg is even going to work
    // before calling it and kicking an exception
    if ( empty($this->args["action"]) ) { $action="failure";} else { $action=$this->resolveArg("action"); }

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage MonitoringPoller Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }

    // This can be empty, so dont get bit here if there are no posted vars
    // This will always be an array
    $data = $this->getFormData();
    $data['action'] = $action;
    if ( ! empty($_GET["cycle"])) { $data['cycle']=$_GET["cycle"]; } else { $data['cycle'] = 0; }
    if ( ! empty($_GET["hostgroup"])) { $data['hostgroup']=$_GET["hostgroup"]; } else { $data['hostgroup']='undefinedHostgroup'; }
    if ( ! empty($_GET['monitor'])) { $data['monitor']=$_GET['monitor']; } else { $data['monitor']='undefinedMonitor'; }
    // return $this->respondWithData($data);
    if("$action" == "all") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitoringPollerAll($data);  // this is an array
    }
    elseif ($action == "disable") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitoringPollerDisable($data);  // this is an array
    }
    elseif ($action == "hostgroup") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitoringId($data);  // this is an array
    }
    elseif ($action == "checkName") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitorsById($data);  // this is an array
    }
    elseif ($action == "hostname") {
      // Make sure idList is a clean string list with no quotes or extras.
      $data['idList']=(string)$data['idList'];
      $data['idList']=str_replace('"','',$data['idList']);
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitoringHostname($data);  // this is an array
    }
    elseif ($action == "heartbeat") {
      // Make sure idList is a clean string list with no quotes or extras.
      $FindMonitoringPoller=$this->monitoringPollerRepository->saveHeartBeat($data);  // this is an array
    }
    elseif ($action == "savePerformance") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->savePerformance($data);  // this is an array
    }
    elseif ($action == "deletePerformance") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->deletePerformance($data);  // this is an array
    }
    elseif ($action == "isAlive") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->saveAlive($data);  // this is an array
    }
    elseif ($action == "housekeeping") {
      $FindMonitoringPoller=$this->monitoringPollerRepository->housekeeping($data);  // this is an array
    }
    else {
      // this is going to be the "normal search
      $FindMonitoringPoller=$this->monitoringPollerRepository->FindMonitoringPoller($data);  // this is an array
    }
    $this->logger->info("Find or save monitoringPoller values for " . $action . " with iteration cycle of " .  $data['cycle']);
    return $this->respondWithData($FindMonitoringPoller);
  }
}
