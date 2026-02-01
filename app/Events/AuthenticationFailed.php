<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthenticationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $account,
        public string $error
    ) {}
}
