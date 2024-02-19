<?php
declare(strict_types=1);

namespace App\Domain\Device;

use JsonSerializable;

class Device implements JsonSerializable {

    /**
     * @var string
     */
  private $hostname;
  private $address;
  private $monitor;
  private $pre_processing;
  private $id;
  private $firstSeen;

  public function __construct($array) {
    $this->hostname = $array['hostname'];
    $this->address = $array['address'];
    $this->monitor = $array['monitor'];
    $this->pre_processing = $array['pre_processing'];
    $this->id = $array['id'];
    $this->firstSeen = $array['firstSeen'];
    }

    public function jsonSerialize() {
      return [
        'hostname' => $this->hostname,
        'address' => $this->address,
        'monitor' => $this->monitor,
        'pre_processing' => $this->pre_processing,
        'id' => $this->id,
        'firstSeen' => $this->firstSeen,
        ];
    }
}
