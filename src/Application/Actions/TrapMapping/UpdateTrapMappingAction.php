<?php
declare(strict_types=1);

namespace App\Application\Actions\TrapMapping;

use Psr\Http\Message\ResponseInterface as Response;

class UpdateTrapMappingAction extends TrapMappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $data=$this->getFormData();
    /*
      We are going to need all POST set to make sure our updates are reliable
    */
    $responseCode=200;  // respond with a not allowed.  oid is manditory

    if (! isset($data['oid'])) {
      $responseCode=405;  // respond with a not allowed.  oid is manditory
      $trapMapping="OID value is manditory to update an existing oid trapMapping, DUH!  Please play again.";
      $this->logger->error("Attempted to update an oid trapMapping without an oid value set");
    }
    /*
      this better be set in the post, but if someone bypasses the route / UI then
      the mess is on them, as there are no safety rails at that point.  Save the
      system not the idiot bypassing the UI.
    */
    if (! isset($data['display_name']))    { $data['display_name']="brokenManualEditByIdiot";}
    if (! isset($data['severity']))        { $data['severity']='5';}
    if (! isset($data['pre_processing']))  { $data['pre_processing']='';}
    if (! isset($data['type']))            { $data['type'] = 0; }
    if (! isset($data['parent_of']))       { $data['parent_of']='';}
    if (! isset($data['child_of']))        { $data['child_of']='';}
    if (! isset($data['age_out']))         { $data['age_out']=86400 ;}
    if (! isset($data['post_processing'])) { $data['post_processing']='';}

    // Return success or failure
    if ( $responseCode !== 405) {
      // Send the $data array over for insert
      $trapMapping = $this->trapMappingRepository->updatetrapMapping($data);
      $this->logger->info("Changed or updated trap trapMapping for " . $data['oid']);
      return $this->respondWithData($trapMapping);
    }
    else {
      return $this->respondWithData("You Failed");
    }
  } // end function
} // end class

