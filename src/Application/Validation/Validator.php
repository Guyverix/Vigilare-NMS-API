<?php

declare(strict_types=1);

namespace App\Application\Validation;

//use Exception;
//use InvalidArgumentException;
//use Slim\Exception\HttpBadRequestException;
//use Slim\Exception\HttpPreconditionRequiredException;

abstract class Validator {
  /**
   * @throws ValidationException
   */
  public function validate(array $data): void {
    try {
      $this->__validate($data);
    }
    catch (InvalidArgumentException $exception) {
      throw new ValidationException($exception->getMessage());
    }
  }

  /**
   * @throws InvalidArgumentException
   */
  abstract public function __validate(array $data): void;
}
