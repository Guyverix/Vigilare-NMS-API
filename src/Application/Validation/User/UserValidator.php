<?php

declare(strict_types=1);

namespace App\Application\Validation\User;

use App\Application\Validation\Validator;
use Webmozart\Assert\Assert;

class UserValidator extends Validator {
  // This is the initial catchall example.
  public function __validate(array $data):void {
    Assert::isArray($data);
  }

  public function setPassword(array $data): void {
    Assert::keyExists($data, "id");
    Assert::keyExists($data, "tpw");
    Assert::keyExists($data, "password");
    Assert::integer($data['id'], 'id must be an integer');
    Assert::minLength($data['password'], 6);
    Assert::maxLength($data['password'], 128);
    Assert::stringNotEmpty($data['password'], "password cannot be empty");
    Assert::stringNotEmpty($data['tpw'], "tpw cannot be empty");
  }

  public function updatePassword(array $data): void {
    Assert::keyExists($data, "username");
    Assert::keyExists($data, "password");
    Assert::stringNotEmpty($data['password'], "password cannot be empty");
    Assert::stringNotEmpty($data['username'], "username cannot be empty");
    Assert::minLength($data['password'], 6);
    Assert::minLength($data['username'], 3);
    Assert::maxLength($data['username'], 128);
    Assert::maxLength($data['password'], 128);
  }
}
