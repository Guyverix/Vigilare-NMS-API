<?php
declare(strict_types=1);

namespace App\Domain\Housekeeping;

use JsonSerializable;

class Housekeeping implements JsonSerializable {

    /**
     * @var string
     */
  private $pid;

  public function __construct() {
//  public function __construct($array) {
//    $this->pid = $array['pid'];
    $this->pid = ["Housekeeping.php constructor"];
    }

    public function jsonSerialize() {
      return [
        'pid' => $this->pid,
        ];
    }
}
