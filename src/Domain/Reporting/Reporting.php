<?php
declare(strict_types=1);

namespace App\Domain\Reporting;

use JsonSerializable;
class Reporting implements JsonSerializable {

    /**
     * @var string
     */
  private $pid;

  public function __construct() {
  }
  public function jsonSerialize() {
    return [
      'nothing' => '',
      ];
  }
}
