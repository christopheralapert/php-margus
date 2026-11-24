<?php

namespace Tests\Unit;

use App\Services\Chat\SafetyFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ChatSafetyFilterTest extends TestCase
{
    #[DataProvider('unsafeReplies')]
    public function test_unsafe_replies_are_replaced(string $text): void
    {
        $reply = app(SafetyFilter::class)->filter($text, 'Rest for 2 days.', 'en');
        $this->assertTrue($reply->fallback);
        $this->assertStringContainsString(config('clinic.clinic.phone'), $reply->text);
        $this->assertStringContainsString(config('clinic.clinic.emergency'), $reply->text);
    }

    public static function unsafeReplies(): array
    {
        return array_map(fn ($text) => [$text], [
            '', '  ', 'Take 5 mg.', 'Use 2,5 ml.', 'Take 1 g.', 'Take 20 µg.',
            'Take 20 mcg.', 'Take 2 tabl.', 'Võta 2 tablett.', 'Take 2 tablets.',
            'Võta 1 kapsel.', 'Take 1 capsule.', 'Take this 3 times a day.',
            'Kasuta 2 korda päevas.', 'You have an infection.',
            'You are suffering from an infection.', 'The diagnosis is an infection.',
            'It is likely an infection.', 'Sul on põletik.', 'Sul tekkis põletik.',
            'Diagnoos on põletik.', 'Tegemist on põletikuga.', str_repeat('word ', 121),
        ]);
    }

    public function test_normal_reply_and_exact_context_dose_are_kept(): void
    {
        $filter = app(SafetyFilter::class);
        $this->assertFalse($filter->filter('Rest and follow the guide.', 'Rest.', 'en')->fallback);
        $this->assertFalse($filter->filter('5 mg', '5 mg', 'en')->fallback);
        $this->assertTrue($filter->filter('5 mg', '15 mg', 'en')->fallback);
    }
}
