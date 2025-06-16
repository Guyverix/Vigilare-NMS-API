<?php
declare(strict_types=1);

namespace App\Application\Actions\TrapMapping;

use Psr\Http\Message\ResponseInterface as Response;

class DeleteTrapMappingAction extends TrapMappingAction
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
      $trapMapping="OID value is manditory to delete an existing oid trapMapping, DUH!  Please play again.";
      $this->logger->error("Attempted to delete an oid trapMapping without an oid value set");
    }
    if ( $valid !== 405) {
      // Send the $data array over for DELETE
      $trapMapping = $this->trapMappingRepository->deletetrapMapping($data);
      $this->logger->info("Deleting trap trapMapping for " . $data['oid']);


      $validation=$this->trapMappingRepository->findOid($data);
      if (!empty($validation)) {
        $trapMapping="Oid trapMapping was not removed from database";
        $this->logger->error($trapMapping);
        throw new HttpBadRequestException($this->request, $trapMapping);
        return $this->respondWithData($trapMapping);
      }
      else {
        return $this->respondWithData($trapMapping);
      }
    }
    else {
      $this->logger->error($trapMapping);
      throw new HttpBadRequestException($this->request, $trapMapping);
      return $this->respondWithData($trapMapping);
    }
  } // end function
} // end class

