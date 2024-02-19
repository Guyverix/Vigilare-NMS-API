<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class CreateMappingAction extends MappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $data=$this->getFormData();
    /* We are going to need all POST set at least for defaults if
       we are going to make reliable database inserts
    */
    $valid=200; // assume success first

    if (! isset($data['oid'])) {
      $valid=405;  // respond with a not allowed.  oid is manditory
      $mapping="OID value is manditory to create a new mapping!  Please try again.";
      $this->logger->error("Attempted to create a new mapping without an oid value set");
    }

    if (! isset($data['display_name']))    { $data['display_name']="unknown";}
    if (! isset($data['severity']))        { $data['severity']='1';}
    if (! isset($data['pre_processing']))  { $data['pre_processing']='';}
    if (! isset($data['type']))            { $data['type'] = 1; }
    if (! isset($data['parent_of']))       { $data['parent_of']='';}
    if (! isset($data['child_of']))        { $data['child_of']='';}
    if (! isset($data['age_out']))         { $data['age_out']=86400 ;}
    if (! isset($data['post_processing'])) { $data['post_processing']='';}

    // Return success or failure

    $validation=$this->mappingRepository->findOid($data);
    if (!empty($validation)) {
      $mapping="Oid Value already mapped.  Use update API";
      $valid=405;
    }

    if ( $valid !== 405) {
      // Send the $data array over for insert
      $mapping = $this->mappingRepository->createMapping($data);
      $this->logger->info("Added new trap mapping for " . $data['oid']);
      return $this->respondWithData($mapping);
    }
    else {
      throw new HttpBadRequestException($this->request, $mapping);
      return $this->respondWithData($mapping);
    }
  } // end function
} // end class

