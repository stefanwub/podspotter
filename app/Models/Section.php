<?php

namespace App\Models;

use App\Services\PineconeService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class Section extends Model
{
    use HasFactory, HasUuids, HasNeighbors;

    protected $guarded = [];
    
    public function toSearchableArray()
    {
        return [
            'content' => $this->content,
        ];
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function vectorObject($vectors)
    {
        return [
            'id' => '' . $this->episode_id . '|' . $this->id,
            'values' => $vectors->toArray(),
            'metadata' => [
                'show_id' => $this->episode->show_id,
                'episode_id' => $this->episode_id
            ]
        ];
    }
}
