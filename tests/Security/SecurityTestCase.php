<?php

namespace Tests\Security;

use App\Services\Chat\FakeLlmClient;
use App\Services\Chat\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['chat.enabled' => true, 'chat.provider' => 'fake']);
        $this->llm = new FakeLlmClient('Rest and follow the guide.');
        $this->app->instance(LlmClient::class, $this->llm);
    }
}
