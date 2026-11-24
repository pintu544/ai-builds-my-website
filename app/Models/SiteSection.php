<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSection extends Model
{
    protected $fillable = ['project_id', 'kind', 'position', 'content', 'fact_keys'];

    protected $casts = [
        'content' => 'array',
        'fact_keys' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
