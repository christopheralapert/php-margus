<?php

namespace Tests\Security;

use App\Models\Category;
use App\Models\Consent;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

final class AccessControlTest extends SecurityTestCase
{
    public function test_foreign_leaflets_are_hidden_for_download_and_deletion(): void
    {
        $leaflet = SavedLeaflet::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->get('/en/account/leaflets/'.$leaflet->uuid.'/pdf')->assertNotFound();
        $this->deleteJson('/en/account/leaflets/'.$leaflet->uuid, ['confirm' => true])->assertNotFound();
        $this->assertModelExists($leaflet);
        $this->assertNull($leaflet->fresh()->last_downloaded_at);
    }

    public function test_every_admin_route_requires_authentication_verification_and_admin_gate(): void
    {
        Procedure::factory()->create(['id' => 1, 'category_id' => Category::factory()->create(['id' => 1])->id]);
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'admin.'));
        $this->assertNotEmpty($routes);
        $ordinary = User::factory()->create();
        $unverified = User::factory()->unverified()->create(['is_admin' => true]);
        foreach ($routes as $route) {
            $uri = '/'.str_replace(['{locale}', '{category}', '{procedure}'], ['en', '1', '1'], $route->uri());
            $method = $route->methods()[0];
            $this->app['auth']->forgetGuards();
            $this->call($method, $uri)->assertRedirect('/en/login');
            $this->actingAs($ordinary)->call($method, $uri)->assertForbidden();
            $this->actingAs($unverified)->call($method, $uri)->assertRedirect('/en/verify-email');
        }
    }

    public static function encodings(): array
    {
        return [['form'], ['json']];
    }

    #[DataProvider('encodings')]
    public function test_extra_fields_cannot_grant_privileges_or_change_catalogue_or_ownership(string $encoding): void
    {
        Notification::fake();
        $procedure = Procedure::factory()->create();
        $other = User::factory()->create();
        $extra = ['is_admin' => true, 'status' => 'published', 'user_id' => $other->id, 'reviewed_by' => 'Untrusted', 'format' => 'invalid'];
        $send = fn ($method, $path, $data) => $encoding === 'json' ? $this->json($method, $path, $data) : $this->call($method, $path, $data);
        $send('POST', '/en/register', $extra + [
            'email' => 'new@example.test', 'password' => 'password', 'password_confirmation' => 'password', 'consent' => true,
        ])->assertRedirect('/en/dashboard');
        $user = User::where('email', 'new@example.test')->sole();
        $this->assertFalse($user->is_admin);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user);
        $send('PATCH', '/en/profile', $extra + ['name' => 'Mari', 'email' => $user->email])->assertRedirect();
        $this->assertFalse($user->fresh()->is_admin);
        $this->withSession(['wizard' => ['procedures' => [$procedure->id]]]);
        $send('POST', '/en/leaflet/details', $extra + ['first_name' => 'Mari', 'consent' => true])->assertRedirect();
        $consent = Consent::where('purpose', 'personalised_leaflet')->sole();
        $this->assertSame($user->id, $consent->user_id);
        $send('POST', '/en/account/leaflets', $extra)->assertInvalid('format');
        $this->assertDatabaseCount('saved_leaflets', 0);
        unset($extra['format']);
        $send('POST', '/en/account/leaflets', $extra)->assertRedirect('/en/account/leaflets');
        $saved = SavedLeaflet::sole();
        $this->assertSame($user->id, $saved->user_id);
        $this->assertSame('a5', $saved->format->value);
        $this->assertSame($procedure->status, $procedure->fresh()->status);
        $this->assertSame($procedure->reviewed_by, $procedure->fresh()->reviewed_by);
        $this->assertFalse($other->fresh()->is_admin);
    }

    public function test_erasure_only_deletes_the_actor_and_cascades_without_tracking_consent_ip_or_agent(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $own = SavedLeaflet::factory()->for($actor)->create();
        $foreign = SavedLeaflet::factory()->for($other)->create();
        $consent = Consent::factory()->for($actor)->create();
        $foreignConsent = Consent::factory()->for($other)->create();
        $this->actingAs($actor)->delete('/en/profile', ['password' => 'password', 'user_id' => $other->id])->assertRedirect('/en');
        foreach ([$actor, $own, $consent] as $model) {
            $this->assertModelMissing($model);
        }
        foreach ([$other, $foreign, $foreignConsent] as $model) {
            $this->assertModelExists($model);
        }
        foreach (Schema::getColumnListing('consents') as $column) {
            $this->assertDoesNotMatchRegularExpression('/(?:ip|agent)/i', $column);
        }
    }
}
