<?php

namespace App\Exceptions;

class TokenNotFoundException extends \Exception
{
    public function __construct(string $account)
    {
        parent::__construct("Token not found for account: {$account}");
    }
}
