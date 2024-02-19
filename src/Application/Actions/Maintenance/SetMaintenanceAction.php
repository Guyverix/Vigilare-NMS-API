<?php
declare(strict_types=1);

namespace App\Application\Actions\Maintenance;

use App\Application\Action;
use App\Application\Actions\Maintenance\MaintenanceAction;
use App\Application\Validation\Maintenance\MaintenanceValidator;
use Psr\Http\Message\ResponseInterface as Response;

class SetMaintenanceAction extends MaintenanceAction {
    protected function action(): Response
    {
        $maintenanceRequest1 = $this->getFormData();
        /* device cannot be guessed, duh */
        if (! isset($maintenanceRequest1['startTime']) ) { $maintenanceRequest1['startTime']=gmdate("Y-m-d H:i:s,\Z"); }
        if (! isset($maintenanceRequest1['endTime'])   ) { $maintenanceRequest1['endTime']='0000-00-00 00:00:00'; }
        if (! isset($maintenanceRequest1['component']) ) { $maintenanceRequest1['component']=""; }

        /* Add more data from $_SERVER for logging */
        $maintenanceRequest = array_merge($maintenanceRequest1, $_SERVER);
        $maintenanceRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        /* make damn sure we have what we need here */
        $validator = new MaintenanceValidator();
        $validator->validate($maintenanceRequest);

        /* setup your request here for domain >> database >> response here */
        $maintenanceResponse=$this->maintenanceRepository->setMaintenance($maintenanceRequest);

        /* log and respond to the query here */
        $this->logger->info("Set Maintenance state for " . $maintenanceRequest1['device'] . ' ' . $maintenanceRequest1['component'] . ".", $maintenanceRequest);
        return $this->respondWithData($maintenanceResponse, $maintenanceRequest['httpResponseCode']);
    }
}
