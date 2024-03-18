<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class MediaFile extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $appends = [
        'audio_url',
        'video_url'
    ];

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function getAudioUrlAttribute()
    {
        if (! $this->audio_storage_key) return '';

        return Storage::disk($this->storage_disk)->url($this->audio_storage_key);
    }

    public function getVideoUrlAttribute()
    {
        if (! $this->video_storage_key) return '';

        return Storage::disk($this->storage_disk)->url($this->video_storage_key);
    }
}
