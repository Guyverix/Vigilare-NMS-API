<?php
declare(strict_types=1);

namespace App\Domain\ShellPoller;

use JsonSerializable;

class ShellPoller implements JsonSerializable {

    /**
     * @var string
     */
  private $pid;

  public function __construct() {
    $this->pid = ["shellPoller.php constructor"];
    }

  public function jsonSerialize() {
    return [
      'pid' => $this->pid,
      ];
  }
}
