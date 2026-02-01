<?php

namespace App\Exceptions;

class InvalidCredentialsException extends \Exception
{
  public function __construct(string $path)
  {
    parent::__construct("Invalid or missing credentials file: {$path}");
  }
}
