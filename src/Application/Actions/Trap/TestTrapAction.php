<?php

declare(strict_types=1);

namespace App\Application\Actions\Trap;

use App\Domain\Trap\Trap;
use App\Domain\Trap\TrapRepository;
use Psr\Http\Message\ResponseInterface as Response;

class TestTrapAction extends TrapAction {

//  public function __construct() {}

  protected function action(): Response {
    $data = $this->getFormData();
    /* Set all manidotory defaults here
       if normal tools are in place, these will not get used
       but if someone calls manually, it had beeter work
       as much as possible */
    if (! isset($data['endEvent']) )      { $data['endEvent']="0000-00-00 00:00:00"; }
    if (! isset($data['evid']) )      { $data['evid']=uniqid(); }
    if (! isset($data['eventSeverity']) ) { $data['eventSeverity']="1"; }
    if (! isset($data['eventReceiver']) ) { $data['eventReceiver']= getHostByName(getHostName()); } /* this will return 127.0.1.1 as well as others */
    if (! isset($data['eventSummary']) )  { $data['eventSummary']="Trap received.  No summary set"; }
    if (! isset($data['eventName']) )     { $data['eventName']="undefined"; }
    if (! isset($data['eventType']) )     { $data['eventType']="3"; }
    if (! isset($data['eventMonitor']) )  { $data['eventMonitor']="3"; }
    if (! isset($data['eventCounter']) )  { $data['eventCounter']="1"; }
    if (! isset($data['eventAddress']) )  { $data['eventAddress']=$_SERVER['REMOTE_ADDR']; } /* If it is not defined, we need SOMETHING, so use calling IP */
    if (! isset($data['eventProxyIp']) )  { $data['eventProxyIp']="0.0.0.0"; }
    if (! isset($data['device']) )    { $data['device']='unknown'; }
    if (! isset($data['eventDetails']) )  { $data['eventDetails']='No event details given'; }
    if (! isset($data['eventAgeOut']) )   { $data['eventAgeOut']='3600' ; }
    if (! isset($data['startEvent']) )    { $data['startEvent']=gmdate("Y-m-d h:i:s"); }
    if (! isset($data['stateChange']) )   { $data['stateChange']=gmdate("Y-m-d h:i:s"); }
    if (! isset($data['eventRaw']) )      { $data['eventRaw']=json_encode($data); } /* last so it gets all data */

    return $this->respondWithData("RESPONSE");
  }
}
