<?php

declare(strict_types=1);

namespace App\Application\Validation\Snmp;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

/*
        Assert::stringNotEmpty($data[''], 'Field `` cannot be empty.');
        Assert::maxLength($data[''], #, 'Field `` be longer than # characters.');
*/


class SnmpOidValidator extends Validator
{
    public function __validate(array $data): void
    {
      Assert::stringNotEmpty($data['hostname'], 'Field `hostname` cannot be empty.');
      Assert::stringNotEmpty($data['oid'], 'Field `oid` cannot be empty.');
      /* ADD MORE VALIDATION HERE IF NEEDED IN THE FUTURE. ASSERT HAS INTERESTING VALIDATION OPTIONS */
    }
}
