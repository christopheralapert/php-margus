<?php

namespace App\Services\Chat;

use Anthropic\Beta\Messages\BetaStopReason;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;

final class AnthropicLlmClient implements LlmClient
{
    public function __construct(private readonly ?Client $client = null) {}

    /** Parameters verified against anthropic-ai/sdk v1.13.0 beta messages create(). */
    public function parameters(string $system, array $messages): array
    {
        return [
            'model' => config('chat.model'),
            'maxTokens' => 1024,
            'system' => $system,
            'messages' => $messages,
            'outputConfig' => ['effort' => config('chat.effort')],
            'betas' => ['server-side-fallback-2026-07-01'],
            'fallbacks' => 'default',
        ];
    }

    public function reply(string $system, array $messages): string
    {
        try {
            $client = $this->client ?? new Client(
                apiKey: (string) config('chat.api_key'),
                baseUrl: config('chat.base_url') ?: null,
                requestOptions: [
                    'timeout' => (float) config('chat.timeout'),
                    'maxRetries' => 0,
                    // SDK timeout is advisory; enforce it in the PSR-18 transport.
                    'transporter' => new \GuzzleHttp\Client(['timeout' => (float) config('chat.timeout')]),
                ],
                openTelemetry: false,
            );
            $message = $client->beta->messages->create(...$this->parameters($system, $messages));

            if ($message->stopReason === 'refusal' || $message->stopReason === BetaStopReason::REFUSAL) {
                return '';
            }

            $text = '';
            foreach ($message->content as $block) {
                if ($block->type === 'text') {
                    $text .= $block->text;
                }
            }

            return $text;
        } catch (APIStatusException|APIConnectionException) {
            throw new ChatUnavailable;
        }
    }
}
