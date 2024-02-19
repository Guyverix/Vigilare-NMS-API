<?php
declare(strict_types=1);

namespace App\Application\Actions\NameMap;

use App\Application\Action;
use App\Application\Actions\NameMap\NameMapAction;
use Psr\Http\Message\ResponseInterface as Response;

/* Using a if statement like this is better I feel for faults and bugs.
   This should keep things nice and stable I hope
*/

class FindNameMapAction extends NameMapAction {
    protected function action(): Response {
        $nameRequest1 = $this->getFormData();

        /* Add more data from $_SERVER for logging */
        $nameRequest = array_merge($nameRequest1, $_SERVER);
        $nameRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        $nameResponse=$this->namemapRepository->findNameMap($nameRequest);
        $this->logger->info("Find name request received", $nameRequest);
        return $this->respondWithData($nameResponse, $nameRequest['httpResponseCode']);
   }
}
