<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name'
    ];

    public function shows() : BelongsToMany
    {
        return $this->belongsToMany(Show::class);
    }

    public function searches() : BelongsToMany
    {
        return $this->belongsToMany(Search::class);
    }
}
