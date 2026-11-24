<?php

namespace Tests\Feature\Models;

use App\Enums\AdviceSection;
use App\Enums\ConsentPurpose;
use App\Enums\LeafletFormat;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Models\AdviceItem;
use App\Models\Category;
use App\Models\Consent;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\Setting;
use App\Models\TimelineStep;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeafletModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_relations_and_content_factory(): void
    {
        $category = Category::factory()->create();
        $later = Procedure::factory()->for($category)->create(['sort' => 20]);
        $first = Procedure::factory()->for($category)->withContent()->create(['sort' => 10]);

        $this->assertSame([$first->id, $later->id], $category->procedures->modelKeys());
        $this->assertTrue($first->category->is($category));
        $this->assertSame(TimelinePhase::cases(), $first->timelineSteps->pluck('phase')->all());
        $this->assertCount(24, $first->adviceItems);
        $this->assertTrue($first->timelineSteps->first()->procedure->is($first));
        $this->assertTrue($first->adviceItems->first()->procedure->is($first));

        foreach (AdviceSection::cases() as $section) {
            $advice = $first->adviceFor($section)->get();
            $this->assertCount(3, $advice);
            $this->assertSame([0, 1, 2], $advice->pluck('sort')->all());
            $this->assertSame([$section], $advice->pluck('section')->unique()->values()->all());
        }
    }

    public function test_timeline_is_ordered_by_phase_before_sort(): void
    {
        $procedure = Procedure::factory()->create();
        $expected = [];

        foreach (array_reverse(TimelinePhase::cases()) as $phase) {
            $later = TimelineStep::factory()->for($procedure)->create(['phase' => $phase, 'sort' => 9]);
            $first = TimelineStep::factory()->for($procedure)->create(['phase' => $phase, 'sort' => 1]);
            array_unshift($expected, $first->id, $later->id);
        }

        $this->assertSame($expected, $procedure->timelineSteps->modelKeys());
    }

    public function test_deleting_a_procedure_cascades_to_its_content(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $other = Procedure::factory()->withContent()->create();
        $procedure->delete();

        $this->assertDatabaseMissing('timeline_steps', ['procedure_id' => $procedure->id]);
        $this->assertDatabaseMissing('advice_items', ['procedure_id' => $procedure->id]);
        $this->assertCount(4, $other->timelineSteps);
        $this->assertCount(24, $other->adviceItems);
    }

    public function test_a_category_with_procedures_cannot_be_deleted(): void
    {
        $procedure = Procedure::factory()->create();

        $this->expectException(QueryException::class);
        $procedure->category->delete();
    }

    public function test_all_translatable_fields_follow_the_application_locale(): void
    {
        $models = [
            Category::factory()->create(),
            Procedure::factory()->create(),
            TimelineStep::factory()->create(),
            AdviceItem::factory()->create(),
        ];

        foreach (['et', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ($models as $model) {
                $model->refresh();

                foreach ($model->getTranslatableAttributes() as $attribute) {
                    $translations = $model->getTranslations($attribute);
                    $this->assertArrayHasKey('et', $translations);
                    $this->assertArrayHasKey('en', $translations);
                    $this->assertSame($translations[$locale], $model->{$attribute});
                }
            }
        }
    }

    public function test_review_note_defaults_and_explicit_overrides(): void
    {
        $procedure = Procedure::factory()->create();
        $this->assertSame('MUSTAND — kliiniku meditsiinipersonal peab üle vaatama', $procedure->getTranslation('review_note', 'et'));
        $this->assertSame('DRAFT — must be reviewed by clinic medical staff', $procedure->getTranslation('review_note', 'en'));

        $note = ['et' => 'Näidis', 'en' => 'Sample'];
        $procedure = Procedure::factory()->create(['review_note' => $note]);
        $this->assertSame($note, $procedure->fresh()->getTranslations('review_note'));
    }

    public function test_theme_color_uses_the_procedure_color_or_category_fallback(): void
    {
        $category = Category::factory()->create(['color' => '#123ABC']);
        $procedure = Procedure::factory()->for($category)->create(['color' => null]);

        $this->assertSame('#123ABC', $procedure->themeColor());
        $procedure->color = '#ABC123';
        $this->assertSame('#ABC123', $procedure->themeColor());
        $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', Category::factory()->create()->color);
    }

    public function test_active_and_published_scopes(): void
    {
        $active = Category::factory()->create();
        Category::factory()->create(['is_active' => false]);
        $draft = Procedure::factory()->draft()->create();
        $published = Procedure::factory()->published()->create();
        $inactive = Procedure::factory()->published()->create(['is_active' => false]);

        $this->assertSame([$active->id, $draft->category_id, $published->category_id, $inactive->category_id], Category::active()->pluck('id')->all());
        $this->assertSame([$published->id, $inactive->id], Procedure::published()->pluck('id')->all());
        $this->assertSame([$published->id], Procedure::active()->published()->pluck('id')->all());
    }

    public function test_enum_and_date_casts_round_trip(): void
    {
        foreach (ProcedureStatus::cases() as $status) {
            $this->assertSame($status, Procedure::factory()->create(['status' => $status])->fresh()->status);
        }
        foreach (TimelinePhase::cases() as $phase) {
            $this->assertSame($phase, TimelineStep::factory()->create(['phase' => $phase])->fresh()->phase);
        }
        foreach (AdviceSection::cases() as $section) {
            $this->assertSame($section, AdviceItem::factory()->create(['section' => $section])->fresh()->section);
        }
        foreach (LeafletFormat::cases() as $format) {
            $leaflet = SavedLeaflet::factory()->create(['format' => $format, 'last_downloaded_at' => now()])->fresh();
            $this->assertSame($format, $leaflet->format);
            $this->assertInstanceOf(Carbon::class, $leaflet->last_downloaded_at);
        }
        foreach (ConsentPurpose::cases() as $purpose) {
            $consent = Consent::factory()->create(['purpose' => $purpose, 'withdrawn_at' => now()])->fresh();
            $this->assertSame($purpose, $consent->purpose);
            $this->assertInstanceOf(Carbon::class, $consent->given_at);
            $this->assertInstanceOf(Carbon::class, $consent->withdrawn_at);
        }
        $procedure = Procedure::factory()->create(['reviewed_at' => now()]);
        $this->assertInstanceOf(Carbon::class, $procedure->fresh()->reviewed_at);
    }

    public function test_saved_leaflet_personal_fields_are_encrypted_and_uuid_is_generated(): void
    {
        $leaflet = SavedLeaflet::factory()->create(['first_name' => 'Mari', 'procedure_date' => '2026-11-24']);
        $raw = DB::table('saved_leaflets')->where('id', $leaflet->id)->first();

        $this->assertTrue(Str::isUuid($leaflet->uuid));
        $this->assertSame($leaflet->uuid, $raw->uuid);
        $this->assertNotSame('Mari', $raw->first_name);
        $this->assertNotSame('2026-11-24', $raw->procedure_date);
        $this->assertSame('Mari', $leaflet->fresh()->first_name);
        $this->assertSame('2026-11-24', $leaflet->fresh()->procedure_date);
        $this->assertSame($leaflet->procedure_ids, $leaflet->fresh()->procedure_ids);

        $anonymous = SavedLeaflet::factory()->create(['first_name' => null, 'procedure_date' => null])->fresh();
        $this->assertNull($anonymous->first_name);
        $this->assertNull($anonymous->procedure_date);
        $this->assertNotSame($leaflet->uuid, $anonymous->uuid);
    }

    public function test_user_relations_nullable_name_defaults_and_erasure(): void
    {
        $user = User::factory()->create(['name' => null, 'last_active_at' => now()]);
        $leaflet = SavedLeaflet::factory()->for($user)->create();
        $consent = Consent::factory()->for($user)->create();
        $anonymousConsent = Consent::factory()->create(['user_id' => null]);

        $this->assertNull($user->fresh()->name);
        $this->assertSame('et', $user->locale);
        $this->assertFalse($user->isAdmin());
        $this->assertInstanceOf(Carbon::class, $user->fresh()->last_active_at);
        $this->assertTrue($user->savedLeaflets->sole()->is($leaflet));
        $this->assertTrue($user->consents->sole()->is($consent));
        $this->assertTrue($leaflet->user->is($user));
        $this->assertTrue($consent->user->is($user));
        $this->assertNull($anonymousConsent->user);
        $user->forceFill(['is_admin' => true])->save();
        $this->assertTrue($user->fresh()->isAdmin());
        $user->delete();

        $this->assertModelMissing($leaflet);
        $this->assertModelMissing($consent);
        $this->assertModelExists($anonymousConsent);
        $this->assertFalse(Schema::hasColumn('consents', 'ip'));
        $this->assertFalse(Schema::hasColumn('consents', 'ip_address'));
        $this->assertFalse(Schema::hasColumn('consents', 'user_agent'));
    }

    public function test_settings_preserve_json_without_translating_it(): void
    {
        $setting = Setting::factory()->create();
        app()->setLocale('en');
        $this->assertSame(['et' => 'Näidisväärtus', 'en' => 'Sample value'], $setting->fresh()->value);
        $setting->update(['value' => ['primary' => '#123ABC']]);
        $this->assertSame(['primary' => '#123ABC'], $setting->fresh()->value);
    }

    public function test_every_enum_case_has_a_label_in_both_locales(): void
    {
        foreach (['et', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ([ProcedureStatus::class, TimelinePhase::class, AdviceSection::class, ConsentPurpose::class, LeafletFormat::class] as $enum) {
                foreach ($enum::cases() as $case) {
                    $this->assertNotEmpty($case->label());
                    $this->assertStringNotContainsString('enums.', $case->label());
                }
            }
        }

        app()->setLocale('et');
        $this->assertSame('Esimesed 24 tundi', TimelinePhase::First24h->label());
        app()->setLocale('en');
        $this->assertSame('First 24 hours', TimelinePhase::First24h->label());
    }
}
