<?php
declare(strict_types=1);

namespace App\Application\Actions\Maintenance;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\Maintenance\MaintenanceAction;
use App\Domain\Maintenance\MaintenanceRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageMaintenanceAction extends MaintenanceAction {
  protected function action(): Response {

    // Define the allowed jobs this class can do
    $jobType = ['findMaintenanceDevice', 'findMaintenanceComponent', 'findMaintenanceStart', 'findMaintenanceEnd', 'findAllMaintenance'];

    /* BOILERPLATE
       This should be a generic way for each route to vet the supported arguments
       everything SHOULD be done this way if at all possible
    */
    // check if resolveArg is even going to work before calling it and kicking an exception
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
      $this->logger->error("ManageMaintenance Action no valid action type defined for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    // pull in any form data we may have
    $data = $this->getFormData();

    /* END BOILERPLATE */

    switch ($action) {
      case 'findMaintenanceDevice':
        $maintenanceResult = $this->maintenanceRepository->findMaintenanceDevice($data);
        break;
      case 'findMaintenanceComponent':
        $maintenanceResult = $this->maintenanceRepository->findMaintenanceComponent($data);
        break;
      case 'findMaintenanceStart':
        $maintenanceResult = $this->maintenanceRepository->findMaintenanceStart($data);
        break;
      case 'findMaintenanceEnd':
        $maintenanceResult = $this->maintenanceRepository->findMaintenanceEnd($data);
        break;
      case 'findAllMaintenance':
        $maintenanceResult = $this->maintenanceRepository->findAllMaintenance();
        break;
      default:
        $this->logger->error("Route called with no valid action set in URL.");
        throw new HttpBadRequestException($this->request, "Route called with no valid action set in URL.");
        break;
    }
    // Assuming no errors, log the query and return the data
    $this->logger->info("Completed " . $action, $data);
    return $this->respondWithData($maintenanceResult);
  }
}

