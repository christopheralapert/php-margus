<?php

namespace App\Services\Chat;

use App\Support\Settings;
use Illuminate\Support\Collection;

final class ContextBuilder
{
    public function __construct(private readonly Settings $settings) {}

    public function contacts(): array
    {
        return [
            'phone' => (string) $this->settings->get('clinic.phone'),
            'after_hours' => (string) $this->settings->get('clinic.after_hours_phone'),
            'emergency' => (string) $this->settings->get('clinic.emergency'),
        ];
    }

    public function build(Collection $procedures, string $locale): string
    {
        $content = [];
        foreach ($procedures->sortBy('slug') as $procedure) {
            $content[] = [
                'name' => $procedure->getTranslation('name', $locale, false),
                'summary' => $procedure->getTranslation('summary', $locale, false),
                'timeline' => $procedure->timelineSteps->map(fn ($step) => [
                    'marker' => $step->getTranslation('marker', $locale, false),
                    'title' => $step->getTranslation('title', $locale, false),
                    'body' => $step->getTranslation('body', $locale, false),
                ])->all(),
                'advice' => $procedure->adviceItems->map(fn ($item) => [
                    'section' => __('chat.sections.'.$item->section->value, [], $locale),
                    'text' => $item->getTranslation('text', $locale, false),
                ])->all(),
                'follow_up' => $procedure->getTranslation('follow_up', $locale, false),
            ];
        }

        return __('chat.system', [...$this->contacts(), 'locale' => $locale], $locale)
            ."\n".json_encode(['contacts' => $this->contacts(), 'procedures' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
