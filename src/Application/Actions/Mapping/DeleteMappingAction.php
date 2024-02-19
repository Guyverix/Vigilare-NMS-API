<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use Psr\Http\Message\ResponseInterface as Response;

class DeleteMappingAction extends MappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $data=$this->getFormData();
    /*
      We are going to need all POST set to make sure our updates are reliable
    */
    $valid=200;
    if (! isset($data['oid'])) {
      $valid=405;  // respond with a not allowed.  oid is manditory
      $mapping="OID value is manditory to delete an existing oid mapping, DUH!  Please play again.";
      $this->logger->error("Attempted to delete an oid mapping without an oid value set");
    }
    if ( $valid !== 405) {
      // Send the $data array over for DELETE
      $mapping = $this->mappingRepository->deleteMapping($data);
      $this->logger->info("Deleting trap mapping for " . $data['oid']);


      $validation=$this->mappingRepository->findOid($data);
      if (!empty($validation)) {
        $mapping="Oid mapping was not removed from database";
        $this->logger->error($mapping);
        throw new HttpBadRequestException($this->request, $mapping);
        return $this->respondWithData($mapping);
      }
      else {
        return $this->respondWithData($mapping);
      }
    }
    else {
      $this->logger->error($mapping);
      throw new HttpBadRequestException($this->request, $mapping);
      return $this->respondWithData($mapping);
    }
  } // end function
} // end class

