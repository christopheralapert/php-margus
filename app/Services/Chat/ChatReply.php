<?php

namespace App\Services\Chat;

final readonly class ChatReply
{
    public function __construct(
        public string $text,
        public bool $red_flag = false,
        public bool $fallback = false,
    ) {}
}
