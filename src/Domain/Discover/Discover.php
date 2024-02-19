<?php
declare(strict_types=1);

namespace App\Domain\Discover;

use JsonSerializable;

class Discover implements JsonSerializable {

    /**
     * @var string
     */
  private $ipAddress;
  private $device;

  public function __construct() {
    $this->ipAddress = $array['ipAddress'];
    $this->device = $array['device'];
    }

    public function jsonSerialize() {
      return [
        'ipAddress' => $this->ipAddress,
        'device' => $this->device,
        ];
    }
}
