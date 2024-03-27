<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'public'
    ];

    protected $casts = [
        'public' => 'boolean'
    ];

    public function clips() : BelongsToMany
    {
        return $this->belongsToMany(Clip::class)
            ->withTimestamps()
            ->withPivot('sort');
    }

    public function team() : BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
