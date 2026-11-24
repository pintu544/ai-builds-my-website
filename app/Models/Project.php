<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['business_name', 'city', 'slug', 'status', 'resolution', 'published_at'];

    protected $casts = [
        'resolution' => 'array',
        'published_at' => 'datetime',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SiteSection::class)->orderBy('position');
    }

    /** Single-value fields where sources disagree (more than one distinct value). */
    public function conflicts(): array
    {
        // 'hours' is deliberately absent: several lines (Mon-Fri + Sat) are one
        // opening-hours fact, not a disagreement.
        $singleValue = ['name', 'city', 'phone', 'email', 'address', 'description'];
        $conflicts = [];
        $grouped = $this->usableFacts()->whereIn('field', $singleValue)->groupBy('field');
        foreach ($grouped as $field => $facts) {
            $distinct = $facts->pluck('value')->map(fn ($v) => $this->normalise($field, $v))->unique();
            if ($distinct->count() > 1) {
                $conflicts[$field] = $facts;
            }
        }
        return $conflicts;
    }

    public function unresolvedConflicts(): array
    {
        $resolution = $this->resolution ?? [];
        $unresolved = [];
        foreach ($this->conflicts() as $field => $facts) {
            $entry = $resolution[$field] ?? null;
            if (! is_array($entry) || ! isset($entry['fact'], $entry['seen'])) {
                $unresolved[$field] = $facts;
                continue;
            }
            // Resolved only for the exact set of values the user saw. A new
            // distinct value from a later source re-opens the conflict.
            $current = $facts->pluck('value')->map(fn ($v) => $this->normalise($field, $v))->unique()->values()->all();
            sort($current);
            $seen = $entry['seen'];
            sort($seen);
            if ($current !== $seen || ! $facts->contains('id', $entry['fact'])) {
                $unresolved[$field] = $facts;
            }
        }
        return $unresolved;
    }

    /** The normalised distinct values of a field, as recorded at resolution time. */
    public function normalisedValues(string $field): array
    {
        $vals = $this->usableFacts()->where('field', $field)
            ->pluck('value')->map(fn ($v) => $this->normalise($field, $v))->unique()->values()->all();
        sort($vals);
        return $vals;
    }

    /** Normalise a value for comparison (phones compare by digits alone). */
    private function normalise(string $field, string $value): string
    {
        if ($field === 'phone') {
            $digits = preg_replace('/\D/', '', $value);
            if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            return $digits;
        }
        return mb_strtolower(trim($value));
    }

    /**
     * Facts usable for generation: everything except unconfirmed facts from
     * web-search results — a search hit might be a different business with
     * the same name, so its facts join the site only once the user confirms
     * at least the value being used.
     */
    private function usableFacts()
    {
        return $this->facts()->with('source')->where('flagged_injection', false)->get()
            ->filter(fn ($f) => $f->confirmed || ($f->source?->type !== 'search'));
    }

    /** The confirmed value for a field: resolution winner, else the single/confirmed fact. */
    public function confirmedValue(string $field): ?string
    {
        $resolution = $this->resolution ?? [];
        $winnerId = is_array($resolution[$field] ?? null) ? ($resolution[$field]['fact'] ?? null) : ($resolution[$field] ?? null);
        if ($winnerId) {
            $fact = $this->facts()->find($winnerId);
            if ($fact) {
                return $fact->value;
            }
        }
        $facts = $this->usableFacts()->where('field', $field);
        $distinct = $facts->pluck('value')->map(fn ($v) => $this->normalise($field, $v))->unique();
        if ($distinct->count() === 1) {
            // Comparison is normalised; the returned value is the original.
            return $facts->first()->value;
        }
        $confirmed = $facts->firstWhere('confirmed', true);
        return $confirmed?->value;
    }

    /** All confirmed facts as field => value(s), for site generation. */
    public function confirmedFacts(): array
    {
        $out = [];
        foreach (['name', 'city', 'phone', 'email', 'address', 'description'] as $field) {
            $v = $this->confirmedValue($field);
            if ($v !== null) {
                $out[$field] = $v;
            }
        }
        $hours = $this->usableFacts()->where('field', 'hours')->pluck('value')->unique()->values()->all();
        if ($hours) {
            $out['hours'] = implode("\n", $hours);
        }
        foreach (['service', 'price'] as $field) {
            $vals = $this->usableFacts()->where('field', $field)
                ->pluck('value')->unique()->values()->all();
            if ($vals) {
                $out[$field . 's'] = $vals;
            }
        }
        return $out;
    }
}
