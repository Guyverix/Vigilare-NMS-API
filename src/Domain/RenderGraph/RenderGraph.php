<?php
declare(strict_types=1);

namespace App\Domain\RenderGraph;

use JsonSerializable;

class RenderGraph implements JsonSerializable {

    /**
     * @var string
     */
  private $evid;
  private $device;
  private $stateChange;
  private $startEvent;
  private $endEvent;
  private $eventAgeOut;
  private $eventCounter;
  private $eventRaw;
  private $eventReceiver;
  private $eventSeverity;
  private $eventAddress;
  private $eventDetails;
  private $eventProxyIp;
  private $eventName;
  private $eventType;
  private $eventMonitor;
  private $eventSummary;
/*
  public function __construct($array) {
    $this->evid = $array['evid'];
    $this->device = $array['device'];
    $this->stateChange = $array['stateChange'];
    $this->startEvent = $array['startEvent'];
    $this->endEvent = $array['endEvent'];
    $this->eventAgeOut = $array['eventAgeOut'];
    $this->eventCounter = $array['eventCounter'];
    $this->eventRaw = $array['eventRaw'];
    $this->eventReceiver = $array['eventReceiver'];
    $this->eventSeverity = $array['eventSeverity'];
    $this->eventAddress = $array['eventAddress'];
    $this->eventDetails = $array['eventDetails'];
    $this->eventProxyIp = $array['eventProxyIp'];
    $this->eventName = $array['eventName'];
    $this->eventType = $array['eventType'];
    $this->eventMonitor = $array['eventMonitor'];
    $this->eventSummary = $array['eventSummary'];
    }

    public function jsonSerialize() {
      return [
        'evid' => $this->evid,
        'device' => $this->device,
        'stateChange' => $this->stateChange,
        'startEvent' => $this->startEvent,
        'endEvent' => $this->endEvent,
        'eventAgeOut' => $this->eventAgeOut,
        'eventCounter' => $this->eventCounter,
        'eventRaw' => $this->eventRaw,
        'eventReceiver' => $this->eventReceiver,
        'eventSeverity' => $this->eventSeverity,
        'eventAddress' => $this->eventAddress,
        'eventDetails' => $this->eventDetails,
        'eventProxyIp' => $this->eventProxyIp,
        'eventName' => $this->eventName,
        'eventType' => $this->eventType,
        'eventMonitor' => $this->eventMonitor,
        'eventSummary' => $this->eventSummary,
        ];
    }
*/
}
