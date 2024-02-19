<?php

declare(strict_types=1);

namespace App\Application\Validation\Trap;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

/*
        Assert::stringNotEmpty($data[''], 'Field `` cannot be empty.');
        Assert::maxLength($data[''], #, 'Field `` be longer than # characters.');
*/


class NewTrapValidator extends Validator
{
    public function __validate(array $data): void
    {
      Assert::stringNotEmpty($data['evid'], 'Field `evid` cannot be empty.');
      /* ADD MORE VALIDATION HERE */
    }
}
