<?php

namespace App\Services\Chat;

use RuntimeException;

final class ChatUnavailable extends RuntimeException
{
    public function __construct()
    {
        // Never retain the SDK exception: its request/body may contain health data.
        parent::__construct('Chat provider unavailable.');
    }
}
