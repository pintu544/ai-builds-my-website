<?php

namespace App\Services\Ai;

class SectionPrompt
{
    public const KINDS = ['hero', 'about', 'services', 'prices', 'gallery', 'contact', 'footer'];

    public static function system(): string
    {
        return 'You write website copy for a small business. You receive a JSON object of CONFIRMED FACTS. '
            . 'Rules: (1) Use ONLY these facts — never invent a phone number, email, address, price, service, timing, award, or claim that is not in the facts. '
            . '(2) If a fact is missing, leave that content out instead of guessing. '
            . '(3) Return ONLY a JSON object of the form {"sections":[{"kind":"hero","content":{...},"fact_keys":["phone", ...]}, ...]} with kinds from: hero, about, services, prices, gallery, contact, footer. '
            . 'Content fields: hero {heading, subheading}; about {heading, body}; services {heading, items[]}; prices {heading, items[]}; gallery {heading}; contact {heading, body}; footer {text}. '
            . 'fact_keys lists the fact keys each section actually used.';
    }

    public static function user(array $facts, ?string $onlyKind): string
    {
        $ask = $onlyKind
            ? 'Generate ONLY the "' . $onlyKind . '" section.'
            : 'Generate all applicable sections (skip a section if its facts are absent, except hero and footer which use name/city).';
        return $ask . "\nCONFIRMED FACTS:\n" . json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Enforce the content shape the views expect: scalar fields must be
     * strings, items must be a list of strings. Returns null when a section
     * is too malformed to render safely.
     */
    private static function sanitise(string $kind, array $content): ?array
    {
        $scalars = ['hero' => ['heading', 'subheading'], 'about' => ['heading', 'body'],
            'services' => ['heading'], 'prices' => ['heading'], 'gallery' => ['heading'],
            'contact' => ['heading', 'body'], 'footer' => ['text']][$kind] ?? [];
        $out = [];
        foreach ($scalars as $field) {
            if (isset($content[$field])) {
                if (! is_string($content[$field])) {
                    return null;
                }
                $out[$field] = $content[$field];
            }
        }
        if (in_array($kind, ['services', 'prices'], true) && isset($content['items'])) {
            if (! is_array($content['items'])) {
                return null;
            }
            $out['items'] = array_values(array_filter($content['items'], 'is_string'));
        }
        return $out;
    }

    /** Parse + validate a model response into section rows. Throws on bad shape. */
    public static function parse(string $raw): array
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new AiUnavailableException('AI response was not JSON.');
        }
        $data = json_decode(substr($raw, $start, $end - $start + 1), true);
        if (! is_array($data) || ! isset($data['sections']) || ! is_array($data['sections'])) {
            throw new AiUnavailableException('AI response had no sections.');
        }
        $sections = [];
        foreach ($data['sections'] as $s) {
            if (! isset($s['kind']) || ! in_array($s['kind'], self::KINDS, true) || ! isset($s['content']) || ! is_array($s['content'])) {
                continue;
            }
            $content = self::sanitise($s['kind'], $s['content']);
            if ($content === null) {
                continue;
            }
            $sections[] = [
                'kind' => $s['kind'],
                'content' => $content,
                'fact_keys' => array_values(array_filter((array) ($s['fact_keys'] ?? []), 'is_string')),
            ];
        }
        if (! $sections) {
            throw new AiUnavailableException('AI response contained no valid sections.');
        }
        return $sections;
    }
}
