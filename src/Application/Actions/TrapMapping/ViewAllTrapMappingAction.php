<?php
declare(strict_types=1);

namespace App\Application\Actions\TrapMapping;

use Psr\Http\Message\ResponseInterface as Response;

class ViewAllTrapMappingAction extends TrapMappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $responseCode=200;
    $trapMapping = $this->trapMappingRepository->findAllOid();
    $this->logger->info("Trap trapMapping from database queried for all oids was retrieved.");
    return $this->respondWithData($trapMapping);

  } // end function
} // end class
