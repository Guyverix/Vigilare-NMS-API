<?php
declare(strict_types=1);

namespace App\Domain\NameMap;

use JsonSerializable;

class NameMap implements JsonSerializable {

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

  public function __construct() {
  }

  public function jsonSerialize() {
  }
}
