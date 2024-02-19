<?php
declare(strict_types=1);

namespace App\Application\Actions\Maintenance;

use App\Application\Action;
use App\Application\Actions\Maintenance\MaintenanceAction;
use Psr\Http\Message\ResponseInterface as Response;

/* Using a if statement like this is better I feel for faults and bugs.
   This should keep things nice and stable I hope
*/

class FindMaintenanceAction extends MaintenanceAction {
    protected function action(): Response {

        $maintenanceRequest1 = $this->getFormData();

        /* Add more data from $_SERVER for logging */
        $maintenanceRequest = array_merge($maintenanceRequest1, $_SERVER);
        $maintenanceRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */
        $responseCode = 200 ;

       if ( isset( $maintenanceRequest1['device']) ) {
          /* setup your request here for domain >> database >> response here */
          $maintenanceResponse=$this->maintenanceRepository->findMaintenanceDevice($maintenanceRequest);
       }
       elseif ( isset( $maintenanceRequest1['component']) ) {
          /* setup your request here for domain >> database >> response here */
          $maintenanceResponse=$this->maintenanceRepository->findMaintenanceComponent($maintenanceRequest);
       }
       elseif ( isset( $maintenanceRequest1['startTime']) ) {
          /* setup your request here for domain >> database >> response here */
          $maintenanceResponse=$this->maintenanceRepository->findMaintenanceStart($maintenanceRequest);
       }
       elseif ( isset( $maintenanceRequest1['endTime']) ) {
          /* setup your request here for domain >> database >> response here */
          $maintenanceResponse=$this->maintenanceRepository->findMaintenanceEnd($maintenanceRequest);
       }
       else {
          /* setup your request here for domain >> database >> response here */
          $maintenanceResponse=$this->maintenanceRepository->findMaintenanceInvalid($maintenanceRequest);
          $maintenanceRequest['httpResponseCode'] = 400;
          $responseCode = 400;
       }
       if ( $maintenanceRequest['httpResponseCode'] == 400 ) {
         $this->logger->error("Invalid API call received", $maintenanceRequest);
       }
       else {
         $this->logger->info("Find maintenance request received", $maintenanceRequest);
       }
       return $this->respondWithData($maintenanceResponse, $maintenanceRequest['httpResponseCode']);
   }
}
