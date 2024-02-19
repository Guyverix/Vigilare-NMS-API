<?php
declare(strict_types=1);

namespace App\Application\Actions\NameMap;

use App\Application\Action;
use App\Application\Actions\NameMap\NameMapAction;
use Psr\Http\Message\ResponseInterface as Response;

class FindAllNameMapAction extends NameMapAction {
    protected function action(): Response {
        $nameRequest['httpResponseCode'] = 200;
        $nameResponse=$this->namemapRepository->findAllNameMap();
        $this->logger->info("Find name request received", $nameRequest);
        return $this->respondWithData($nameResponse, $nameRequest['httpResponseCode']);
   }
}
