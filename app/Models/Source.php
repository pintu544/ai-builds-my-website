<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = ['project_id', 'type', 'url', 'path', 'status', 'raw_text', 'note'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }

    public function label(): string
    {
        return match ($this->type) {
            'link' => 'Website link',
            'google' => 'Google Business listing',
            'social' => 'Social media link',
            'pdf' => 'Uploaded PDF',
            'pricelist' => 'Price list file',
            'photo' => 'Photo',
            'search' => 'Web search result',
            'manual' => 'Pasted by user',
            default => ucfirst($this->type),
        };
    }
}
