<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $account
    ) {}
}
