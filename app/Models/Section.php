<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class Section extends Model
{
    use HasFactory, HasUuids, HasNeighbors, Searchable;

    protected $guarded = [];

    protected $casts = ['embedding' => Vector::class];

    public function toSearchableArray()
    {
        return [
            'content' => $this->content,
        ];
    }
}
