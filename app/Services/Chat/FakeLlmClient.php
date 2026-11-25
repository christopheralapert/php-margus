<?php

namespace App\Services\Chat;

final class FakeLlmClient implements LlmClient
{
    public ?array $lastRequest = null;

    public int $calls = 0;

    public bool $unavailable = false;

    public function __construct(public ?string $response = null) {}

    public function reply(string $system, array $messages): string
    {
        $this->lastRequest = ['system' => $system, 'messages' => $messages];
        $this->calls++;

        if ($this->unavailable) {
            throw new ChatUnavailable;
        }

        return $this->response ?? __('chat.fake_reply');
    }
}
