<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fact extends Model
{
    protected $fillable = ['project_id', 'source_id', 'field', 'value', 'snippet', 'confirmed', 'flagged_injection'];

    protected $casts = [
        'confirmed' => 'boolean',
        'flagged_injection' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
