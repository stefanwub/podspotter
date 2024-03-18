<?php

namespace App\Models;

use App\Jobs\DownloadEpisodeMedia;
use App\Jobs\RenderClip;
use Bus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Storage;

class Clip extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $appends = [
        'duration',
        'url'
    ];

    protected static function booted()
    {
        self::saving(function (Clip $clip): void {
            if ($clip->start_region !== $clip->getOriginal('start_region') || $clip->end_region !== $clip->getOriginal('end_region')) {
                $clip->status = 'processing';
            }
        });

        self::saved(function (Clip $clip): void {
            if ($clip->status === 'processing' && $clip->getOriginal('status') !== 'processing') {
                Bus::chain([
                    new DownloadEpisodeMedia($clip->episode),
                    new RenderClip($clip)
                ])->dispatch();
            }
        });
    }

    public function team() : BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function posts() : HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getDurationAttribute()
    {
        return $this->end_region - $this->start_region;
    }

    public function getUrlAttribute()
    {
        if (! $this->storage_key) return '';

        return Storage::disk($this->storage_disk)->url($this->storage_key);
    }
}
