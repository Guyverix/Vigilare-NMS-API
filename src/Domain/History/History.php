<?php
declare(strict_types=1);

namespace App\Domain\History;

use JsonSerializable;

class History implements JsonSerializable
{
    /**
     * @var string|null
     */
private $evid;
    /**
     * @var string
     */
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

    /**
     * @param int|null  $id
     * @param string    $username
     * @param string    $firstName
     * @param string    $lastName
     */
    public function __construct(?string $evid, string $device, string $stateChange, string $startEvent, string $endEvent, string $eventAgeOut, string $eventCounter, string $eventRaw, string $eventReceiver, string $eventSeverity, string $eventAddress, string $eventDetails, string $eventProxyIp, string $eventName, string $eventType , string $eventMonitor , string $eventSummary )
    {
        $this->evid = $evid;
        $this->device = $device;
        $this->stateChange = $stateChange;
        $this->startEvent = $startEvent;
        $this->endEvent = $endEvent;
        $this->eventAgeOut = $eventAgeOut;
        $this->eventCounter = $eventCounter;
        $this->eventRaw = $eventRaw;
        $this->eventReceiver = $eventReceiver;
        $this->eventSeverity = $eventSeverity;
        $this->eventAddress = $eventAddress;
        $this->eventDetails = $eventDetails;
        $this->eventProxyIp = $eventProxyIp;
        $this->eventName = $eventName;
        $this->eventType = $eventType;
        $this->eventMonitor = $eventMonitor;
        $this->eventSummary = $eventSummary;

    }

    /**
     * @return string|null
     */
    public function getEvid(): ?string
    {
        return strval($this->evid);
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public function getStateChange(): string
    {
        return $this->stateChange;
    }

    public function getStartEvent(): string
    {
        return $this->startEvent;
    }
    public function getEndEvent(): string
    {
        return $this->endEvent;
    }
    public function getEventAgeOut(): string
    {
        return $this->eventAgeOut;
    }
    public function getEventCounter(): string
    {
        return $this->eventCounter;
    }
    public function getEventRaw(): string
    {
        return $this->eventRaw;
    }
    public function getEventReceiver(): string
    {
        return $this->eventReceiver;
    }
    public function getEventSeverity(): string
    {
        return $this->eventSeverity;
    }
    public function getEventAddress(): string
    {
        return $this->eventAddress;
    }
    public function getEventDetails(): string
    {
        return $this->eventDetails;
    }
    public function getEventProxyIp(): string
    {
        return $this->eventProxyIp;
    }
    public function getEventName(): string
    {
        return $this->eventName;
    }
    public function getEventType(): string
    {
        return $this->eventType;
    }
    public function getEventMonitor(): string
    {
        return $this->eventMonitor;
    }
    public function getEventSummary(): string
    {
        return $this->eventSummary;
    }

    public function jsonSerialize()
    {
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
}
