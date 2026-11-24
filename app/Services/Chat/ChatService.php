<?php

namespace App\Services\Chat;

use App\Models\Procedure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ChatService
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly RedFlagDetector $redFlags,
        private readonly PiiScrubber $scrubber,
        private readonly ContextBuilder $context,
        private readonly SafetyFilter $safety,
    ) {}

    public function handle(string $locale, array $procedureSlugs, string $message, array $history = [], ?string $firstName = null): ChatReply
    {
        if (! in_array($locale, config('app.supported_locales'), true)
            || count($procedureSlugs) < 1 || count($procedureSlugs) > 5
            || count(array_unique($procedureSlugs)) !== count($procedureSlugs)) {
            throw ValidationException::withMessages(['procedures' => __('chat.validation', [], $locale)]);
        }

        $procedures = Procedure::active()->with(['timelineSteps', 'adviceItems'])
            ->whereIn('slug', $procedureSlugs)->orderBy('slug')->get();
        if ($procedures->count() !== count($procedureSlugs)) {
            throw ValidationException::withMessages(['procedures' => __('chat.validation', [], $locale)]);
        }

        $count = $this->increment('messages');
        // Emergency advice remains available even after the provider budget is exhausted.
        if ($this->redFlags->detects($message, $procedures, $locale)) {
            $this->increment('red_flags');

            return new ChatReply(__('chat.red_flag', $this->context->contacts(), $locale), red_flag: true);
        }

        if ($count > (int) config('chat.daily_cap')) {
            $this->increment('fallbacks');

            return new ChatReply(__('chat.busy', $this->context->contacts(), $locale), fallback: true);
        }

        $messages = [];
        foreach (array_slice($history, -6) as $turn) {
            if (in_array($turn['role'] ?? null, ['user', 'assistant'], true) && is_string($turn['content'] ?? null)) {
                $messages[] = [
                    'role' => $turn['role'],
                    // Scrub before truncating so a cut-off identifier cannot leak.
                    'content' => mb_substr($this->scrubber->scrub($turn['content'], $firstName), 0, (int) config('chat.max_chars')),
                ];
            }
        }
        $messages[] = [
            'role' => 'user',
            'content' => mb_substr($this->scrubber->scrub($message, $firstName), 0, (int) config('chat.max_chars')),
        ];
        $system = $this->context->build($procedures, $locale);

        try {
            $reply = $this->safety->filter($this->llm->reply($system, $messages), $system, $locale);
        } catch (Throwable) {
            // SDK/request exceptions can embed prompts. Do not log or report them.
            $reply = $this->safety->fallback($locale);
        }

        if ($reply->fallback) {
            $this->increment('fallbacks');
        }

        return $reply;
    }

    private function increment(string $counter): int
    {
        $key = 'chat:'.now()->format('Y-m-d').':'.$counter;
        Cache::add($key, 0, now()->endOfDay());

        return (int) Cache::increment($key);
    }
}
