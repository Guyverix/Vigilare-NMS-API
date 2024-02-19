<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use Psr\Http\Message\ResponseInterface as Response;

class ViewAllMappingAction extends MappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $responseCode=200;
    $mapping = $this->mappingRepository->findAllOid();
    $this->logger->info("Trap mapping from database queried for all oids was retrieved.");
    return $this->respondWithData($mapping);

  } // end function
} // end class
