<?php

namespace App\Services\Chat;

final class SafetyFilter
{
    public function __construct(private readonly ContextBuilder $context) {}

    public function fallback(string $locale): ChatReply
    {
        return new ChatReply(__('chat.fallback', $this->context->contacts(), $locale), fallback: true);
    }

    public function filter(string $reply, string $context, string $locale): ChatReply
    {
        $diagnosis = '/\b(?:you have|you are suffering from|diagnosis is|it is likely an infection|sul on|sul tekkis|diagnoos on|tegemist on)\b/iu';
        $dose = '/(?<![\p{L}\p{N}])\d+(?:[.,]\d+)?\s?(?:mg|ml|g|µg|mcg|tabl|tablett|tablet|tablets|kapsel|capsule)\b|\b\d+\s*(?:times a day|korda päevas)\b/iu';

        if (trim($reply) === '' || preg_match($diagnosis, $reply) || count(preg_split('/\s+/u', trim($reply))) > 120) {
            return $this->fallback($locale);
        }

        preg_match_all($dose, $reply, $replyDoses);
        preg_match_all($dose, $context, $contextDoses);
        $normalize = fn (string $value): string => str_replace(',', '.', preg_replace('/\s+/u', '', mb_strtolower($value)));
        $allowed = array_map($normalize, $contextDoses[0]);
        foreach ($replyDoses[0] as $match) {
            if (! in_array($normalize($match), $allowed, true)) {
                return $this->fallback($locale);
            }
        }

        return new ChatReply(trim($reply));
    }
}
