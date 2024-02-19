<?php
declare(strict_types=1);

namespace App\Domain\Authentication;

use JsonSerializable;

class Authentication implements JsonSerializable {

    /**
     * @var string
     */
  private $data;
}
