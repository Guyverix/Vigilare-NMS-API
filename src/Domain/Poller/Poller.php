<?php
declare(strict_types=1);

namespace App\Domain\Poller;

use JsonSerializable;

class Poller implements JsonSerializable {

    /**
     * @var string
     */
  private $pid;

  public function __construct() {
//  public function __construct($array) {
//    $this->pid = $array['pid'];
    $this->pid = ["Poller.php constructor"];
    }

    public function jsonSerialize() {
      return [
        'pid' => $this->pid,
        ];
    }
}
