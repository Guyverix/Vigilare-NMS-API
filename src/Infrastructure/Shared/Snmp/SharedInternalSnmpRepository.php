<?php
declare(strict_types=1);

namespace App\Infrastructure\Shared\SharedInternalSnmpRepository;

use App\Domain\InternalSnmp\InternalSnmp;
use App\Domain\InternalSnmp\InternalSnmpRepository;
use App\Domain\InternalSnmp\InternalSnmpNotFoundException;

class SharedInternalSnmpRepository implements InternalSnmpRepository {
  public function returnSnmpOid($arr): array { return $arr; }
}
