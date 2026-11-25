<?php

namespace Tests\Security;

use App\Models\Procedure;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;

final class ChatSafetyTest extends SecurityTestCase
{
    private Procedure $procedure;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->procedure = Procedure::factory()->withContent()->create();
        app(Settings::class)->set('clinic.phone', '+372 612 3456');
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['procedures' => [$this->procedure->slug], 'message' => 'How should I rest?'], $extra);
    }

    public function test_emergency_returns_clinic_contacts_without_calling_llm(): void
    {
        $response = $this->postJson('/en/chat', $this->payload(['message' => 'I cannot breathe, chest pain']))
            ->assertOk()->assertJsonPath('red_flag', true);
        $this->assertStringContainsString('+372 612 3456', $response->json('reply'));
        $this->assertStringContainsString('112', $response->json('reply'));
        $this->assertSame(0, $this->llm->calls);
    }

    public static function identifiers(): array
    {
        return array_map(fn ($text) => [$text], [
            'Mari mari@example.test +372 5123 4567 48912310000 31.12.1989',
            'Mari mari@example.test +372.5123.4567 4 891231 0000 31 . 12 . 1989',
            'Mari mari@example.test 5123.4567 489.1231.0000 1989 - 12 - 31',
            'Mari mari@example.test 00372 (5123) 4567 489-1231-0000 31 / 12 / 1989',
            'Mari mari @ example . test +372.5123.4567 4 891231 0000 31 . 12 . 1989',
        ]);
    }

    #[DataProvider('identifiers')]
    public function test_all_history_roles_and_message_are_scrubbed_before_llm(string $text): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Mari']));
        $this->postJson('/en/chat', $this->payload(['message' => $text, 'history' => [
            ['role' => 'user', 'content' => $text], ['role' => 'assistant', 'content' => $text],
        ]]))->assertOk();
        $this->assertCount(3, $this->llm->lastRequest['messages']);
        foreach ($this->llm->lastRequest['messages'] as $turn) {
            $this->assertSame('[name] [email] [phone] [id] [date]', $turn['content']);
        }
    }

    public static function unsafeReplies(): array
    {
        return [['Take 750 mg daily.'], ['You have an infection.'], ['Sul on infektsioon.']];
    }

    #[DataProvider('unsafeReplies')]
    public function test_dose_and_diagnosis_are_replaced_with_safe_fallback(string $reply): void
    {
        $this->llm->response = $reply;
        $response = $this->postJson('/en/chat', $this->payload())->assertOk()->assertJsonPath('fallback', true);
        $this->assertStringNotContainsString($reply, $response->json('reply'));
        $this->assertStringContainsString('+372 612 3456', $response->json('reply'));
    }

    public function test_context_contains_only_selected_procedures_and_no_patient_identity(): void
    {
        $this->procedure->update(['summary' => ['en' => 'Selected care marker', 'et' => 'Valitud']]);
        Procedure::factory()->withContent()->create(['summary' => ['en' => 'Unselected care marker', 'et' => 'Teine']]);
        $user = User::factory()->create(['name' => 'PatientSecret']);
        $this->actingAs($user)->postJson('/en/chat', $this->payload())->assertOk();
        $system = $this->llm->lastRequest['system'];
        $this->assertStringContainsString('Selected care marker', $system);
        foreach (['Unselected care marker', $user->name, $user->email] as $secret) {
            $this->assertStringNotContainsString($secret, $system);
        }
    }

    public function test_caps_reject_abuse_before_llm_and_do_not_flash_message_content(): void
    {
        foreach ([['message' => str_repeat('x', 501)], ['history' => array_fill(0, 7, ['role' => 'user', 'content' => 'Private'])],
            ['history' => [['role' => 'user', 'content' => str_repeat('x', 501)]]], ['procedures' => array_fill(0, 6, 'slug')]] as $extra) {
            $this->postJson('/en/chat', $this->payload($extra))->assertUnprocessable()->assertSessionMissing('_old_input');
        }
        $this->assertSame(0, $this->llm->calls);
    }

    public function test_session_rate_limit_stops_provider_calls(): void
    {
        $this->withSession([])->withCredentials()->withCookie(config('session.cookie'), session()->getId());
        for ($i = 0; $i < 8; $i++) {
            $this->postJson('/en/chat', $this->payload())->assertOk();
        }
        $this->postJson('/en/chat', $this->payload())->assertStatus(429);
        $this->assertSame(8, $this->llm->calls);
    }

    public function test_ip_rate_limit_spans_sessions_and_stops_provider_calls(): void
    {
        for ($i = 0; $i < 30; $i++) {
            session()->regenerate();
            $this->postJson('/en/chat', $this->payload())->assertOk();
        }
        session()->regenerate();
        $this->postJson('/en/chat', $this->payload())->assertStatus(429);
        $this->assertSame(30, $this->llm->calls);
    }

    public function test_success_emergency_validation_and_provider_failure_never_log_messages(): void
    {
        Log::spy();
        $this->postJson('/en/chat', $this->payload(['message' => 'Private care question']))->assertOk();
        $this->postJson('/en/chat', $this->payload(['message' => 'Chest pain']))->assertOk();
        $this->postJson('/en/chat', $this->payload(['message' => str_repeat('private ', 100)]))->assertUnprocessable();
        $this->llm->unavailable = true;
        $this->postJson('/en/chat', $this->payload())->assertOk()->assertJsonPath('fallback', true);
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'log'] as $method) {
            Log::shouldNotHaveReceived($method);
        }
    }
}
