<?php
declare(strict_types=1);

namespace App\Domain\EventCorrelation;

use JsonSerializable;

class EventCorrelation implements JsonSerializable {
  private $id;

  public function jsonSerialize() {
    return [];
  }
}
