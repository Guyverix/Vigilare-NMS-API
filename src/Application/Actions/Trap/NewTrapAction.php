<?php

declare(strict_types=1);

namespace App\Application\Actions\Trap;

use App\Application\Validation\Trap\NewTrapValidator;
use App\Application\Actions\Trap\TrapAction;
use App\Domain\Trap\Trap;
use App\Domain\Trap\TrapRepository;
use Psr\Http\Message\ResponseInterface as Response;

class NewTrapAction extends TrapAction {

  /* This damn thing needs to exist
     and I cannot figure out why.  What is
     it initializing that does not get set in Trap.php?
  */
  protected function action(): Response {
        $data = $this->getFormData();
        /* Set all manidotory defaults here
           if normal tools are in place, these will not get used
           but if someone calls manually, it had beeter work
           as much as possible */
        if (! isset($data['endEvent']) )      { $data['endEvent']="0000-00-00 00:00:00"; } /* only used to END an event */
        if (! isset($data['evid']) )          { $data['evid']=uniqid(); } /* Give me a unique GUID */
        if (! isset($data['eventSeverity']) ) { $data['eventSeverity']="1"; } /* 0=ok, 1=debug, 2=info, 3=warning, 4=major, 5=critical */
        if (! isset($data['eventReceiver']) ) { $data['eventReceiver']= getHostByName(getHostName().'.'); } /* this will return 127.0.1.1 as well as others Use an extra period to NOT get 127.0.1.1 */
        if (! isset($data['eventSummary']) )  { $data['eventSummary']="URL Trap received.  No summary set.  You failed!"; } /* Summary catchall for crap alarms */
        if (! isset($data['eventName']) )     { $data['eventName']="undefined"; } /* name for key in db.  Unique per check */
        if (! isset($data['eventType']) )     { $data['eventType']="3"; }
        if (! isset($data['eventMonitor']) )  { $data['eventMonitor']="3"; }
        if (! isset($data['eventCounter']) )  { $data['eventCounter']="1"; } /* Ignored if there is a dupe key */
        if (! isset($data['eventAddress']) )  { $data['eventAddress']=$_SERVER['REMOTE_ADDR']; } /* If it is not defined, we need SOMETHING, so use calling IP */
        if (! isset($data['eventProxyIp']) )  { $data['eventProxyIp']="0.0.0.0"; } /* should rarely be seen when trapforwarding is used for something */
        if (! isset($data['device']) )        { $data['device']='unknown'; } /* should not see this often */
        if (! isset($data['eventAgeOut']) )   { $data['eventAgeOut']='3600' ; } /* Used for a trigger to move stuff to history */
        if (! isset($data['startEvent']) )    { $data['startEvent']=gmdate("Y-m-d H:i:s"); } /* just what it says on the label.  Now if not defined */
        if (! isset($data['stateChange']) )   { $data['stateChange']=gmdate("Y-m-d H:i:s"); } /* In general matches startEvent */
        if (! isset($data['eventRaw']) )      { $data['eventRaw']=json_encode($data); } /* last so it gets all data */
        // if (! isset($data['eventDetails']) )  { $data['eventDetails']="No event Details found"; } /* generally a match to raw, but can have MORE values that are appended */
        if (! isset($data['eventDetails']) )  { $data['eventDetails']=json_encode($data['eventSummary'],1); } /* generally a match to raw, but can have MORE values that are appended */
        if ( ! isset($data['application'])) { $data['application'] = "false"; }
        if ( ! isset($data['customerVisible'])) { $data['customerVisible'] = "false"; }
        if ( ! isset($data['osEvent'])) { $data['osEvent'] = "false"; }


        if ( $data['eventSeverity'] == '0' && $data['endEvent'] =="0000-00-00 00:00:00") {
          /* If this is an OK (0 severity) set the endEvent to now */
          $data['endEvent']=gmdate("Y-m-d h:i:s");
        }
        $validator = new NewTrapValidator();
        $validator->validate($data);
        $this->logger->debug("NewTrapAction: Web Trap validator called ", $data);
        /* After validation, run through mapping for transforms */
        $data['oid'] = $data['eventName'];
        $preMap = $this->trapRepository->returnMap($data);          // Returns single match against trapEventMap
        $preMap = json_decode(json_encode($preMap,1), true);
        $this->logger->debug("NewTrapAction: PreMapping results from query ", $preMap);
        // return $this->respondWithData($preMap);
        $data['mapSeverity']       = $preMap[0]['severity'];
        $data['mapAgeOut']         = $preMap[0]['age_out'];
        $data['mapPreProcessing']  = $preMap[0]['pre_processing'];     // This one is critical
        $data['mapPostProcessing'] = $preMap[0]['post_processing'];
        $data['mapType']           = $preMap[0]['type'];
        $data['mapDisplayName']    = $preMap[0]['display_name'];

        /* we now have the data needed to do transform on the event */
        $this->logger->debug("NewTrapAction: Pre-Processing data to be mapped ", $data);
        try {
          $mapping = $this->trapRepository->useMapping($data);
        }
        catch (Throwable $t) {
          $this->logger->error("NewTrapAction: Pre-Processing data result ", $t);
        }
        $this->logger->info("NewTrapAction: Pre-Processing data result ", $mapping);

        /* Make the object that contains all our post data */
        $this->logger->debug("NewTrapAction: Attempt to create Event ", $mapping);
        $trap = $this->trapRepository->createEvent($mapping);
        $this->logger->info("NewTrapAction: Create Event result ", $trap);

        /* If defined weare going to alter the event we just now created */
        if ( ! empty($data['mapPostProcessing'])) {
          $this->logger->debug("NewTrapAction: Post-Processing begins against data: ", $mapping);
          $postProcessing = $this->trapRepository->postMapping($mapping);
          $this->logger->debug("NewTrapAction: Post-processing end result: ", $postProcessing);
        }


        // DEBUGGING ONLY FOR API KEY
        $header = $this->request->getHeaders();
        $this->logger->debug("NewTrapAction: Headers and api keys " , $header);

        /* if using $trap it uses the jsonserialize function from Trap */
        $this->logger->info("NewTrapAction: Web Trap received via web interface for evid " . $data['evid'] . "." . "Hostname: " . $data['device'] . " Severity: " . $data['eventSeverity'] ,$data);
        return $this->respondWithData($trap);
  }
}
