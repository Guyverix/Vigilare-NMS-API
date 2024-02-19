<?php
declare(strict_types=1);

namespace App\Domain\NrpePoller;

use JsonSerializable;

class NrpePoller implements JsonSerializable {

    /**
     * @var string
     */
  private $pid;

  public function __construct() {
    $this->pid = ["nrpePoller.php constructor"];
    }

  public function jsonSerialize() {
    return [
      'pid' => $this->pid,
      ];
  }
}
