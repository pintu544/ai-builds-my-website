<?php

namespace App\Services;

use App\Models\Project;

/**
 * Deterministic, facts-only site builder. Used when the AI is down or
 * unconfigured, and as the safety net if AI output fails the fact guard.
 * It cannot invent anything: every string comes from confirmed facts.
 */
class FallbackSiteBuilder
{
    public function build(Project $project, ?string $onlyKind = null): array
    {
        $f = $project->confirmedFacts();
        $name = $f['name'] ?? $project->business_name;
        $city = $f['city'] ?? $project->city;
        $sections = [];

        $add = function (string $kind, array $content, array $keys) use (&$sections, $onlyKind) {
            if ($onlyKind && $kind !== $onlyKind) {
                return;
            }
            $sections[] = ['kind' => $kind, 'content' => $content, 'fact_keys' => $keys];
        };

        $add('hero', [
            'heading' => $name,
            'subheading' => isset($f['description']) ? $f['description'] : ('Welcome to ' . $name . ' in ' . $city . '.'),
        ], ['name', 'city', isset($f['description']) ? 'description' : 'city']);

        if (isset($f['description'])) {
            $add('about', ['heading' => 'About ' . $name, 'body' => $f['description']], ['description', 'name']);
        }
        if (! empty($f['services'])) {
            $add('services', ['heading' => 'Our services', 'items' => $f['services']], ['services']);
        }
        if (! empty($f['prices'])) {
            $add('prices', ['heading' => 'Prices', 'items' => $f['prices']], ['prices']);
        }
        if ($project->sources()->where('type', 'photo')->exists()) {
            $add('gallery', ['heading' => 'Gallery'], []);
        }
        $contactLines = [];
        $keys = [];
        foreach (['phone' => 'Phone', 'email' => 'Email', 'address' => 'Address', 'hours' => 'Hours'] as $k => $label) {
            if (isset($f[$k])) {
                $contactLines[] = $label . ': ' . $f[$k];
                $keys[] = $k;
            }
        }
        if ($contactLines) {
            $add('contact', ['heading' => 'Contact us', 'body' => implode("\n", $contactLines)], $keys);
        }
        $add('footer', ['text' => $name . ' · ' . $city], ['name', 'city']);

        return $sections;
    }
}
