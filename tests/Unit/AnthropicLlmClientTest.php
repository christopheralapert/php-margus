<?php

namespace Tests\Unit;

use Anthropic\Client;
use App\Services\Chat\AnthropicLlmClient;
use App\Services\Chat\ChatUnavailable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AnthropicLlmClientTest extends TestCase
{
    private function client(MockHandler $handler, array &$requests): AnthropicLlmClient
    {
        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::history($requests));
        $sdk = new Client(
            apiKey: 'test-key',
            requestOptions: ['transporter' => new HttpClient(['handler' => $stack]), 'maxRetries' => 0],
            openTelemetry: false,
        );

        return new AnthropicLlmClient($sdk);
    }

    private function response(string $stopReason = 'end_turn'): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant',
            'model' => 'claude-opus-5-5', 'stop_reason' => $stopReason,
            'stop_sequence' => null, 'container' => null, 'context_management' => null,
            'diagnostics' => null, 'stop_details' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
            'content' => [
                ['type' => 'thinking', 'thinking' => 'Private reasoning', 'signature' => 'test'],
                ['type' => 'text', 'text' => 'Rest ', 'citations' => null],
                ['type' => 'text', 'text' => 'well.', 'citations' => null],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    public function test_actual_sdk_request_and_text_blocks_without_network(): void
    {
        config(['chat.model' => 'claude-opus-5-5', 'chat.effort' => 'low']);
        $requests = [];
        $client = $this->client(new MockHandler([$this->response()]), $requests);
        $messages = [['role' => 'user', 'content' => 'Rest?']];
        $this->assertSame('Rest well.', $client->reply('System rules', $messages));
        $this->assertCount(1, $requests);
        $request = $requests[0]['request'];
        $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame(1024, $body['max_tokens']);
        $this->assertSame(['effort' => 'low'], $body['output_config']);
        $this->assertSame('System rules', $body['system']);
        $this->assertSame($messages, $body['messages']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        foreach (['thinking', 'temperature', 'top_p', 'top_k', 'metadata', 'thread', 'user_profile_id'] as $key) {
            $this->assertArrayNotHasKey($key, $body);
        }
    }

    public function test_sdk_refusal_returns_empty_reply(): void
    {
        $requests = [];
        $client = $this->client(new MockHandler([$this->response('refusal')]), $requests);
        $this->assertSame('', $client->reply('Rules', [['role' => 'user', 'content' => 'Rest?']]));
    }

    #[DataProvider('failures')]
    public function test_sdk_errors_are_replaced_by_content_free_domain_exception(bool $connection): void
    {
        $requests = [];
        $error = $connection
            ? new ConnectException('Sensitive request content', new Request('POST', config('app.url')))
            : new Response(500, ['Content-Type' => 'application/json'], '{"type":"error","error":{"type":"api_error","message":"Sensitive request content"}}');
        $client = $this->client(new MockHandler([$error]), $requests);
        try {
            $client->reply('Sensitive request content', [['role' => 'user', 'content' => 'Sensitive request content']]);
            $this->fail('Expected ChatUnavailable');
        } catch (ChatUnavailable $exception) {
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('Sensitive request content', $exception->getMessage());
        }
    }

    public static function failures(): array
    {
        return [[false], [true]];
    }
}
