<?php
declare(strict_types=1);

namespace App\Application\Actions\NameMap;

use App\Application\Action;
use App\Application\Actions\NameMap\NameMapAction;
use App\Application\Validation\NameMap\NameMapValidator;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateNameMapAction extends NameMapAction {
    protected function action(): Response
    {
        $nameRequest1 = $this->getFormData();
        /* device cannot be guessed, duh */
        if (! isset($nameRequest1['oid']) ) { $nameRequest1['oid']=""; }
        if (! isset($nameRequest1['name'])   ) { $nameRequest1['name']=''; }

        /* Add more data from $_SERVER for logging */
        $nameRequest = array_merge($nameRequest1, $_SERVER);
        $nameRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        /* make damn sure we have what we need here */
        $validator = new NameMapValidator();
        $validator->validate($nameRequest);

        /* setup your request here for domain >> database >> response here */
        $nameResponse=$this->namemapRepository->updateNameMap($nameRequest);

        /* log and respond to the query here */
        $this->logger->info("Set NameMap state for " . $nameRequest1['oid'] . ' to ' . $nameRequest1['name'] . ".", $nameRequest);
        return $this->respondWithData($nameResponse, $nameRequest['httpResponseCode']);
    }
}
class DeleteNameMapAction extends NameMapAction {
    protected function action(): Response
    {
        $nameRequest1 = $this->getFormData();
        /* device cannot be guessed, duh */
        if (! isset($nameRequest1['oid']) ) { $nameRequest1['oid']=""; }
        if (! isset($nameRequest1['name'])   ) { $nameRequest1['name']=''; }

        /* Add more data from $_SERVER for logging */
        $nameRequest = array_merge($nameRequest1, $_SERVER);
        $nameRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        /* make damn sure we have what we need here */
        $validator = new NameMapValidator();
        $validator->validate($nameRequest);

        /* setup your request here for domain >> database >> response here */
        $nameResponse=$this->namemapRepository->deleteNameMap($nameRequest);

        /* log and respond to the query here */
        $this->logger->info("Set NameMap state for " . $nameRequest1['oid'] . ' to ' . $nameRequest1['name'] . ".", $nameRequest);
        return $this->respondWithData($nameResponse, $nameRequest['httpResponseCode']);
    }
}
