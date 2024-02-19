<?php

declare(strict_types=1);

namespace App\Application\Validation\Maintenance;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

/*
        Assert::stringNotEmpty($data[''], 'Field `` cannot be empty.');
        Assert::maxLength($data[''], #, 'Field `` be longer than # characters.');
*/


class MaintenanceValidator extends Validator
{
    public function __validate(array $data): void
    {
      Assert::stringNotEmpty($data['device'], 'Field `device` cannot be empty.');
      /* ADD MORE VALIDATION HERE IF NEEDED IN THE FUTURE. ASSERT HAS INTERESTING
         METHODS IN /opt/nmsApi/vendor/webmozart/assert/src/Assert.php */
    }
}
