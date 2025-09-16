<?php
declare(strict_types=1);

namespace App\Application\Actions\Monitors;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\Monitors\MonitorsAction;
use App\Domain\Monitors\MonitorsRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageMonitorsAction extends MonitorsAction {
  protected function action(): Response {
    $jobType=["findAlarmCount", "findMonitorsByHostId", "findHostGroup", "findDeviceId", "findMonitorType", "findMonitorStorage", "findMonitorIteration", "createMonitor", "updateMonitor", "deleteMonitor", "monitorAddHost", "monitorAddHostgroup", "findMonitors", "findMonitorNames", "findMonitorsDisable", "findMonitorsAll", "findMonitorsByCheckName", "monitorDeleteHost", "monitorDeleteHostGroup"]; // sanity check that we only are doing what we expect here

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
      $this->logger->error("ManageMonitors Action no valid action type defined for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    $data = $this->getFormData();
    $monitoringChanges = array();

    switch ($action) {
    case 'createMonitor':
      $monitoringChanges = $this->monitorsRepository->createMonitor($data);
      break;
    case 'updateMonitor':
      $monitoringChanges = $this->monitorsRepository->updateMonitor($data);
      break;
    case 'deleteMonitor':
      $monitoringChanges = $this->monitorsRepository->deleteMonitor($data['id']);
      break;
    case 'monitorAddHost':
      if (! is_array($data['hostId']) && substr_count($data['hostId'], ',') !== 0) {
        $hostList = explode(',', $data['hostId']);
        $splitData = $data;
        foreach ($hostList as $hl) {
          $splitData['hostId'] = $hl;
          $monitoringChanges[] = $this->monitorsRepository->monitorAddHost($splitData);
        }
      }
      else {
        $monitoringChanges = $this->monitorsRepository->monitorAddHost($data);
      }
      break;
    case 'monitorDeleteHost':
      if (! is_array($data['hostId']) && substr_count($data['hostId'], ',') !== 0) {
        $hostList = explode(',', $data['hostId']);
        $splitData = $data;
        foreach ($hostList as $hl) {
          $splitData['hostId'] = trim($hl);
          $monitoringChanges[] = $this->monitorsRepository->monitorDeleteHost($splitData);
        }
      }
      else {
        $monitoringChanges = $this->monitorsRepository->monitorDeleteHost($data);
      }
      break;
    case 'monitorAddHostgroup':
      if (! is_array($data['hostGroup']) && substr_count($data['hostGroup'], ',') !== 0) {
        $hostgroupList = explode(',', $data['hostGroup']);
        $splitData = $data;
        foreach ($hostgroupList as $hgl) {
          $splitData['hostGroup'] = trim($hgl);
          $monitoringChanges[] = $this->monitorsRepository->monitorAddHostgroup($splitData);
        }
      }
      else {
        $monitoringChanges = $this->monitorsRepository->monitorAddHostgroup($data);
      }
      break;
    case 'monitorDeleteHostGroup':
      if (! is_array($data['hostGroup']) && substr_count($data['hostGroup'], ',') !== 0) {
        $hostgroupList = explode(',', $data['hostGroup']);
        $splitData = $data;
        foreach ($hostgroupList as $hgl) {
          $splitData['hostGroup'] = $hgl;
          $monitoringChanges[] = $this->monitorsRepository->monitorDeleteHostGroup($splitData);
        }
      }
      else {
        $monitoringChanges = $this->monitorsRepository->monitorDeleteHostGroup($data);
      }
      break;
    case 'findMonitors':
      $monitoringChanges = $this->monitorsRepository->findMonitors();
      break;
    case 'findMonitorsByHostId':
      $monitoringChanges = $this->monitorsRepository->findMonitorsByHostId($data);
      break;
    case 'findMonitorNames':
      $monitoringChanges = $this->monitorsRepository->findMonitorNames();
      break;
    case 'findMonitorsDisable':
      $monitoringChanges = $this->monitorsRepository->findMonitorsDisable();
      break;
    case 'findMonitorsAll':
      $monitoringChanges = $this->monitorsRepository->findMonitorsAll();
      break;
    case 'findMonitorsByCheckName':
      $monitoringChanges = $this->monitorsRepository->findMonitorsByCheckName($data);
      break;
    case 'findMonitorType':
      $monitoringChanges = $this->monitorsRepository->findMonitorType();
      break;
    case 'findMonitorStorage':
      $monitoringChanges = $this->monitorsRepository->findMonitorStorage();
      break;
    case 'findMonitorIteration':
      $monitoringChanges = $this->monitorsRepository->findMonitorIteration();
      break;
    case 'findDeviceId':
      $monitoringChanges = $this->monitorsRepository->findDeviceId();
      break;
    case 'findHostGroup':
      $monitoringChanges = $this->monitorsRepository->findHostGroup();
      break;
    case 'findAlarmCount':
      $monitoringChanges = $this->monitorsRepository->findAlarmCount();
      break;
    }

    $this->logger->info("Monitoring change request for " . $action . '.', $data);
    return $this->respondWithData($monitoringChanges);
  }
}

