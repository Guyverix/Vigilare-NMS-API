<?php
declare(strict_types=1);

namespace App\Application\Actions\Maintenance;

use App\Application\Action;
use App\Application\Validation\Maintenance\MaintenanceValidator;
use App\Application\Actions\Maintenance\MaintenanceAction;
use Psr\Http\Message\ResponseInterface as Response;

class EndMaintenanceAction extends MaintenanceAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $maintenanceRequest1 = $this->getFormData();
        /* device cannot be guessed, duh */
        if (! isset($maintenanceRequest1['endTime']) ) { $maintenanceRequest1['endTime']=gmdate("Y-m-d H:i:s\Z"); }
        if (! isset($maintenanceRequest1['component']) ) { $maintenanceRequest1['component']=""; }

        /* Add more data from $_SERVER for logging */
        $maintenanceRequest = array_merge($maintenanceRequest1, $_SERVER);
        $maintenanceRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        $validator = new MaintenanceValidator();
        $validator->validate($maintenanceRequest);

        /* setup your request here for domain >> database >> response here */
        $maintenanceResponse=$this->maintenanceRepository->endMaintenance($maintenanceRequest);

        /* log and respond to the query here */
        $this->logger->info("End Maintenance state for " . $maintenanceRequest1['device'] . ' ' . $maintenanceRequest1['component'] . ".", $maintenanceRequest);
        return $this->respondWithData($maintenanceResponse, $maintenanceRequest['httpResponseCode']);
    }
}
