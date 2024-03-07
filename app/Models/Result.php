<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'sections',
        'description',
        'order',
        'alert',
        'query',
        'episode_id',
        'published_at',
        'indexed_at',
    ];

    protected $casts = [
        'sections' => 'json',
        'order' => 'integer',
        'alert' => 'boolean',
        'published_at' => 'datetime',
        'indexed_at' => 'datetime'
    ];

    public function search() : BelongsTo
    {
        return $this->belongsTo(Search::class);
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
