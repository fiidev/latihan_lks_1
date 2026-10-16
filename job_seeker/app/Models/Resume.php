<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resume extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job_applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
