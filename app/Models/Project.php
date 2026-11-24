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
        $singleValue = ['name', 'city', 'phone', 'email', 'address', 'hours', 'description'];
        $conflicts = [];
        $grouped = $this->facts()->where('flagged_injection', false)->whereIn('field', $singleValue)->get()->groupBy('field');
        foreach ($grouped as $field => $facts) {
            $distinct = $facts->pluck('value')->map(fn ($v) => mb_strtolower(trim($v)))->unique();
            if ($distinct->count() > 1) {
                $conflicts[$field] = $facts;
            }
        }
        return $conflicts;
    }

    public function unresolvedConflicts(): array
    {
        $resolution = $this->resolution ?? [];
        return array_filter(
            $this->conflicts(),
            fn ($field) => ! array_key_exists($field, $resolution),
            ARRAY_FILTER_USE_KEY
        );
    }

    /** The confirmed value for a field: resolution winner, else the single/confirmed fact. */
    public function confirmedValue(string $field): ?string
    {
        $resolution = $this->resolution ?? [];
        if (isset($resolution[$field])) {
            $fact = $this->facts()->find($resolution[$field]);
            if ($fact) {
                return $fact->value;
            }
        }
        $facts = $this->facts()->where('field', $field)->where('flagged_injection', false)->get();
        $distinct = $facts->pluck('value')->unique();
        if ($distinct->count() === 1) {
            return $distinct->first();
        }
        $confirmed = $facts->firstWhere('confirmed', true);
        return $confirmed?->value;
    }

    /** All confirmed facts as field => value(s), for site generation. */
    public function confirmedFacts(): array
    {
        $out = [];
        foreach (['name', 'city', 'phone', 'email', 'address', 'hours', 'description'] as $field) {
            $v = $this->confirmedValue($field);
            if ($v !== null) {
                $out[$field] = $v;
            }
        }
        foreach (['service', 'price'] as $field) {
            $vals = $this->facts()->where('field', $field)->where('flagged_injection', false)
                ->get()->pluck('value')->unique()->values()->all();
            if ($vals) {
                $out[$field . 's'] = $vals;
            }
        }
        return $out;
    }
}
