<?php

namespace App\Services\Chat;

interface LlmClient
{
    /** @param list<array{role: 'user'|'assistant', content: string}> $messages */
    public function reply(string $system, array $messages): string;
}
