<?php

declare(strict_types=1);

namespace App\Application\Validation\NameMap;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

/*
        Assert::stringNotEmpty($data[''], 'Field `` cannot be empty.');
        Assert::maxLength($data[''], #, 'Field `` be longer than # characters.');
*/


class NameMapValidator extends Validator {
    public function __validate(array $data): void {
      Assert::stringNotEmpty($data['name'], 'Field `name` cannot be empty.');
      Assert::stringNotEmpty($data['oid'], 'Field `oid` cannot be empty.');
      /* ADD MORE VALIDATION HERE IF NEEDED IN THE FUTURE. ASSERT HAS INTERESTING
         METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */
    }
}
