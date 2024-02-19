<?php
declare(strict_types=1);

namespace App\Domain\Graphite;

use JsonSerializable;

class Graphite implements JsonSerializable {

    /**
     * @var string
     */
  private $hostname;
  private $graphiteRegex;

  public function __construct() {
    $this->hostname = $array['hostname'];
    $this->graphiteRegex = $array['graphiteRegex'];
    }

    public function jsonSerialize() {
      return [
        'hostname' => $this->hostname,
        'graphiteRegex' => $this->graphiteRegex,
        ];
    }
}
