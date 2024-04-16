<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Trap;

use App\Domain\Trap\Trap;
use App\Domain\Trap\TrapNotFoundException;
use App\Domain\Trap\TrapRepository;
use Database;

class DatabaseTrapRepository implements TrapRepository {
  public $db;

  // Database declared in Repository
  public function __construct() {
    $this->db = new Database();
  }

  private function updateEvent($data) { // This should not be able to be called except internally
    if ( is_array($data['eventRaw'])) { $data['eventRaw'] = json_encode($data['eventRaw'],1); }
    if ( is_array($data['eventDetails'])) { $data['eventDetails'] = json_encode($data['eventDetails'],1); }
//    return $data;

    $this->db->prepare("UPDATE event SET device= :device, eventAddress= :eventAddress, eventAgeOut= :eventAgeOut, eventCounter= :eventCounter, eventReceiver= :eventReceiver, eventSeverity= :eventSeverity, eventDetails= :eventDetails, eventProxyIp= :eventProxyIp, eventName= :eventName, eventType= :eventType, eventMonitor= :eventMonitor, eventSummary= :eventSummary, application= :application, customerVisible= :customerVisible, osEvent= :osEvent WHERE evid= :evid");
    $this->db->bind('device',         $data['device']);
    $this->db->bind('eventAddress',   $data['eventAddress']);
    $this->db->bind('eventAgeOut',    $data['eventAgeOut']);
    $this->db->bind('eventCounter',   $data['eventCounter']);
    $this->db->bind('eventReceiver',  $data['eventReceiver']);
    $this->db->bind('eventSeverity',  $data['eventSeverity']);
    $this->db->bind('eventDetails',   $data['eventDetails']);
    $this->db->bind('eventProxyIp',   $data['eventProxyIp']);
    $this->db->bind('eventName',      $data['eventName']);
    $this->db->bind('eventType',      $data['eventType']);
    $this->db->bind('eventMonitor',   $data['eventMonitor']);
    $this->db->bind('eventSummary',   $data['eventSummary']);
    $this->db->bind('application',    $data['application']);
    $this->db->bind('osEvent',        $data['osEvent']);
    $this->db->bind('customerVisible', $data['customerVisible']);
    $this->db->bind('evid',           $data['evid']);
    $updateDb = $this->db->execute();
    return [ "Update success for evid " . $data['evid'] ];
  }

  public function createEvent($data):array { // Better name for what it is doing
//    $data['eventSeverity'] = (int)$data['eventSeverity'];
    if ( is_array($data['eventRaw'])) { $data['eventRaw'] = json_encode($data['eventRaw'],1); }
    if ( is_array($data['eventDetails'])) { $data['eventDetails'] = json_encode($data['eventDetails'],1); }
    if ( ! isset($data['application'])) { $data['application'] = "false"; }
    if ( ! isset($data['customerVisible'])) { $data['customerVisible'] = "false"; }
    if ( ! isset($data['osEvent'])) { $data['osEvent'] = "false"; }
    if ( $data['eventSeverity'] == 0) {
      $data['endEvent'] = gmdate("Y-m-d H:i:s");
      $this->db->prepare("INSERT INTO event VALUES( :evid, :device, :stateChange, :startEvent, :endEvent, :eventAgeOut, :eventCounter, :eventRaw, :eventReceiver, :eventSeverity, :eventAddress, :eventDetails, :eventProxyIp, :eventName, :eventType, :eventMonitor, :eventSummary, :application, :customerVisible, :osEvent ) ON DUPLICATE KEY UPDATE eventSummary= :eventSummary1, eventDetails= :eventDetails1, eventRaw= :eventRaw1, eventName= :eventName1, stateChange= :stateChange1, endEvent= :endEvent1, evid= :evid1, eventSeverity= :eventSeverity1");
      $this->db->bind('evid',           $data['evid']);
      $this->db->bind('device',         $data['device']);
      $this->db->bind('stateChange',    $data['stateChange']);
      $this->db->bind('startEvent',     $data['startEvent']);
      $this->db->bind('endEvent',       $data['endEvent']);
      $this->db->bind('eventAgeOut',    $data['eventAgeOut']);
      $this->db->bind('eventCounter',   $data['eventCounter']);
      $this->db->bind('eventRaw',       $data['eventRaw']);
      $this->db->bind('eventReceiver',  $data['eventReceiver']);
      $this->db->bind('eventSeverity',  $data['eventSeverity']);
      $this->db->bind('eventAddress',   $data['eventAddress']);
      $this->db->bind('eventDetails',   $data['eventDetails']);
      $this->db->bind('eventProxyIp',   $data['eventProxyIp']);
      $this->db->bind('eventName',      $data['eventName']);
      $this->db->bind('eventType',      $data['eventType']);
      $this->db->bind('eventMonitor',   $data['eventMonitor']);
      $this->db->bind('eventSummary',   $data['eventSummary']);

      $this->db->bind('application',    $data['application']);
      $this->db->bind('osEvent',        $data['osEvent']);
      $this->db->bind('customerVisible', $data['customerVisible']);

      $this->db->bind('eventSummary1',  $data['eventSummary']);
      $this->db->bind('eventDetails1',  $data['eventDetails']);
      $this->db->bind('eventRaw1',      $data['eventRaw']);
      $this->db->bind('eventName1',     $data['eventName']);
      $this->db->bind('stateChange1',   $data['stateChange']);
      $this->db->bind('endEvent1',      "now()");
      $this->db->bind('evid1',          $data['evid']);
      $this->db->bind('eventSeverity1', $data['eventSeverity']);
    }
    else {
      $this->db->prepare("INSERT INTO event VALUES( :evid, :device, :stateChange, :startEvent, :endEvent, :eventAgeOut, :eventCounter, :eventRaw, :eventReceiver, :eventSeverity, :eventAddress, :eventDetails, :eventProxyIp, :eventName, :eventType, :eventMonitor, :eventSummary, :application, :customerVisible, :osEvent ) ON DUPLICATE KEY UPDATE eventCounter= eventCounter +1, eventSummary= :eventSummary1, eventDetails= :eventDetails1, eventRaw= :eventRaw1, eventName= :eventName1, stateChange= :stateChange1, eventSeverity= :eventSeverity1, endEvent= :endEvent1");
      $this->db->bind('evid',           $data['evid']);
      $this->db->bind('device',         $data['device']);
      $this->db->bind('stateChange',    $data['stateChange']);
      $this->db->bind('startEvent',     $data['startEvent']);
      $this->db->bind('endEvent',       $data['endEvent']);
      $this->db->bind('eventAgeOut',    $data['eventAgeOut']);
      $this->db->bind('eventCounter',   $data['eventCounter']);
      $this->db->bind('eventRaw',       $data['eventRaw']);
      $this->db->bind('eventReceiver',  $data['eventReceiver']);
      $this->db->bind('eventSeverity',  $data['eventSeverity']);
      $this->db->bind('eventAddress',   $data['eventAddress']);
      $this->db->bind('eventDetails',   $data['eventDetails']);
      $this->db->bind('eventProxyIp',   $data['eventProxyIp']);
      $this->db->bind('eventName',      $data['eventName']);
      $this->db->bind('eventType',      $data['eventType']);
      $this->db->bind('eventMonitor',   $data['eventMonitor']);
      $this->db->bind('eventSummary',   $data['eventSummary']);

      $this->db->bind('application',    $data['application']);
      $this->db->bind('osEvent',        $data['osEvent']);
      $this->db->bind('customerVisible', $data['customerVisible']);

      $this->db->bind('eventSummary1',  $data['eventSummary']);
      $this->db->bind('eventDetails1',  $data['eventDetails']);
      $this->db->bind('eventRaw1',      $data['eventRaw']);
      $this->db->bind('eventName1',     $data['eventName']);
      $this->db->bind('stateChange1',   $data['stateChange']);
      $this->db->bind('eventSeverity1', $data['eventSeverity']);
      $this->db->bind('endEvent1',      $data['endEvent']);
//      $this->db->bind('evid1',          $data['evid']);
    }
    $result = $this->db->execute();
    // $data['eventSeverity'] = (int)$data['eventSeverity'];
    if ( $data['eventSeverity'] < 1 ) {
      // $this->db->prepare("INSERT INTO history SELECT e.* FROM event e WHERE evid= :evid LIMIT 1");
      // $this->db->bind('evid', $data['evid']);
      // $result2 = $this->db->execute();
      $result2 = 'done via MySQL trigger';
      $this->db->prepare("DELETE FROM event WHERE device= :device AND eventName= :eventName");
      $this->db->bind('device',         $data['device']);
      $this->db->bind('eventName',      $data['eventName']);
      // $this->db->bind('evid',$data['evid']);
      $result3 = $this->db->execute();
      return ["Clear event moved into history table", "Create history row " . json_encode($result2,1), "Delete from event table " . json_encode($result3,1) ];
    }
    return ["Event inserted into event table", "Insert into event table " . json_encode($result,1) ];
  }

  public function returnNew($data): array {
    // Deprecated.  Used to be the event create function using direct PDO instead of class now in use
  }

  public function returnHost($data): array {
    $this->db->prepare=("SELECT * FROM Device WHERE address= :address LIMIT 1");
    $this->db->bind('address', $data['address']);
    $data=$this->db->resultset();
    return $data;
  }

  public function returnMap($data): array {
    $this->db->prepare("SELECT severity, display_name, age_out, type, pre_processing, post_processing FROM trapEventMap WHERE oid= :oid OR oid=\"*\" ORDER BY oid DESC LIMIT 1");
    $this->db->bind('oid', $data['oid']);
    $data=$this->db->resultset();
    return $data;
  }

  public function returnPreMap($data): array {
    $this->db->prepare("SELECT pre_processing FROM trapEventMap WHERE oid= :oid  LIMIT 1");
    $this->db->bind('oid', $data['oid']);
    $data=$this->db->resultset();
    return $data;
  }

  public function returnPostMap($data): array {
    $this->db->prepare("SELECT post_processing FROM trapEventMap WHERE oid= :oid LIMIT 1");
    $this->db->bind('oid', $data['oid']);
    $data=$this->db->resultset();
    return $data;
  }
  public function useMapping($data): array {
    if ( ! empty($data['mapPreProcessing'])) {
      /* Set our current default values */
      if ( ! isset($data['application'])) { $data['application'] = "false"; }
      if ( ! isset($data['customerVisible'])) { $data['customerVisible'] = "false"; }
      if ( ! isset($data['osEvent'])) { $data['osEvent'] = "false"; }

      $evid           = $data['evid'];
      $known_hostname = $data['device'];
      $receive_time   = $data['stateChange'];
      $event_age_out  = $data['eventAgeOut'];
      $counter        = $data['eventCounter'];
      $event_details  = $data['eventDetails'];
      $details        = $data['eventRaw'];
      $receiver       = $data['eventReceiver'];
      $event_severity = $data['eventSeverity'];
      $event_ip       = $data['eventAddress'];
      $event_source   = $data['eventProxyIp'];
      $event_name     = $data['eventName'];
      $event_type     = $data['eventType'];
      $monitor        = $data['eventMonitor'];
      $event_summary  = $data['eventSummary'];
      $event_monitor  = $data['eventMonitor'];

      // New vars to support ECE and reporting
      $osEvent        = $data['osEvent'];
      $customerVisible = $data['customerVisible'];
      $application    = $data['application'];

      /* Set defaults from trapMapping table */
      $preProcessing  = $data['mapPreProcessing'];
      $event_age_out  = $data['mapAgeOut'];
      $event_type     = $data['mapType'];
      $event_severity = $data['mapSeverity'];
      $event_name     = $data['mapDisplayName'];

      if ( ! is_array($details)) { $details = json_decode(json_encode($details,1), true); }
      $details_array = $details;                                  // Having a second copy might be useful if manipulation is done to $details
      if (is_null($details)) { $details = array(); }
      $result['preMappingChanges'] = get_defined_vars();
      /*

      Tried eval() alternative, but was not happy with results

      $content = '<?php ' . $preProcessing . ' ?>';
      $file = dirname(__FILE__) . '/'. $event_name . '.php';
      file_put_contents($file, $content);
      include "$file";
      unlink "$file";
      $result['file'] = $file;
      */

      /* Now eval() will change values */
      eval($preProcessing);                               // In theory this should be able to change our defined values
      if ( $data['eventSeverity'] == 0 ) {                // Mappings cannot make a clear event a set event
        $result['eventSeverity'] = 0 ;
      }
      else {
        $result['eventSeverity'] = (int)$event_severity;
      }
      $result['mapAgeOut']= $data['mapAgeOut'];
      $result['mapDisplayName']= $data['mapDisplayName'];
      $result['mapSeverity'] = $data['mapSeverity'];
      $result['evid'] = $evid;
      $result['device'] = $known_hostname;
      $result['stateChange'] = $receive_time;
      $result['eventAgeOut'] = $event_age_out;
      $result['eventCounter'] = $counter;
      $result['eventDetails'] = $details;
      $result['eventReceiver'] = $receiver;
      $result['eventAddress'] = $event_ip;
      $result['eventProxyIp'] = $event_source;
      $result['eventName'] = $event_name;
      $result['eventType'] = $event_type;
      $result['eventSummary'] = $event_summary;
      $result['eventMonitor'] = $event_monitor;
      $result['osEvent'] = $osEvent;
      $result['customerVisible'] = $customerVisible;
      $result['application'] = $application;


      /* Stuff that is not allowed to be changed via mappings */
      $result['eventRaw']   = $data['eventRaw'];
      $result['startEvent'] = $data['startEvent'];
      $result['endEvent']   = $data['endEvent'];
      $result['status']     = "useMapping() may have changed values";
      $result['postMappingChanges'] = get_defined_vars();

      return $result;
    }
    else {
      $data['eventAgeOut'] = $data['mapAgeOut'];
      $data['eventType']   = $data['mapType'];
      $data['origionalSeverity'] = $data['eventSeverity'];
      if ( ! empty($data['mapDisplayName'])) { $data['eventName'] = $data['mapDisplayName']; }
      //      $data['eventName'] = $data['mapDisplayName'];
      if ( $data['eventSeverity'] > 0 ) {                 // Mappings cannot make a clear event a set event
        $data['eventSeverity'] = $data['mapSeverity'];
      }
      $data['status'] = "useMapping function only changed ageOut, eventName, type, and severity (if not clear) with mapped default values";
      return $data;
    }
  }

  public function postMapping($initial):array {
    // First get our event from the database

    // return [ $initial['preMappingChanges']['data']['mapPostProcessing'] ] ;
    $this->db->prepare("SELECT * FROM event WHERE device= :device AND eventAddress= :eventAddress AND eventName= :eventName");
    $this->db->bind('device', $initial['device']);
    $this->db->bind('eventAddress', $initial['eventAddress']);
    $this->db->bind('eventName', $initial['eventName']);
    $data2 = $this->db->resultset();
    $data2 = json_decode(json_encode($data2,1), true);
    $data = $data2[0];
//return [ $data['device'] ];

    // Make variables we can alter now
    $evid           = $data['evid'];
    $known_hostname = $data['device'];
    $receive_time   = $data['stateChange'];
    $event_age_out  = $data['eventAgeOut'];
    $counter        = $data['eventCounter'];
    $event_details  = $data['eventDetails'];
    $details        = $data['eventRaw'];
    $receiver       = $data['eventReceiver'];
    $event_severity = $data['eventSeverity'];
    $event_ip       = $data['eventAddress'];
    $event_source   = $data['eventProxyIp'];
    $event_name     = $data['eventName'];
    $event_type     = $data['eventType'];
    $monitor        = $data['eventMonitor'];
    $event_summary  = $data['eventSummary'];
    $event_monitor  = $data['eventMonitor'];
    $osEvent        = $data['osEvent'];
    $customerVisible = $data['customerVisible'];
    $application    = $data['application'];


    // Grab what we need to eval now
    if ( ! empty( $initial['preMappingChanges']['data']['mapPostProcessing']) ) {
      $postProcessing = $initial['preMappingChanges']['data']['mapPostProcessing'];
    }
    else {
      $postProcessing = '';
    }

    // Additional boilerplate to confirm we have some of the ugly info if users need it
    if ( ! is_array($details)) {
      $details = json_decode(json_encode($details,1), true);
    }
    $details_array = $details;  // Having a second copy might be useful if manipulation is done to $details
    if (is_null($details)) {
      $details = array();
    }
    try {
      eval($postProcessing);     // In theory this should be able to change our defined values
    }
    catch (Throwable $t) {
     return [ $t ];
    }
    //return $postProcessing;
    $result['eventSeverity']   = $event_severity;
    $result['evid']            = $evid;
    $result['device']          = $known_hostname;
    $result['stateChange']     = $receive_time;
    $result['eventAgeOut']     = $event_age_out;
    $result['eventCounter']    = $counter;
    $result['eventDetails']    = $details;
    $result['eventReceiver']   = $receiver;
    $result['eventAddress']    = $event_ip;
    $result['eventProxyIp']    = $event_source;
    $result['eventName']       = $event_name;
    $result['eventType']       = $event_type;
    $result['eventSummary']    = $event_summary;
    $result['eventMonitor']    = $event_monitor;
    $result['osEvent']         = $osEvent;
    $result['customerVisible'] = $customerVisible;
    $result['application']     = $application;
    $result['eventRaw']        = $data['eventRaw'];    // Dont allow manipulation of these values
    $result['startEvent']      = $data['startEvent'];  // Dont allow manipulation of these values
    $result['endEvent']        = $data['endEvent'];    // Dont allow manipulation of these values

    $finalResult = $this->updateEvent($result);
    return $finalResult;
  }

}


