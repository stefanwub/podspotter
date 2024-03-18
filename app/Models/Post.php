<?php

namespace App\Models;

use App\Jobs\CreatePostByTemplateName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $appends = [
        'url',
        'download_url'
    ];

    protected static function booted()
    {
        self::saved(function (Post $post): void {
            if ($post->status === 'processing' && $post->template_name && $post->template_name !== $post->getOriginal('template_name')) {
                CreatePostByTemplateName::dispatch($post)->onQueue('audio');
            }
        });
    }

    public function clip() : BelongsTo
    {
        return $this->belongsTo(Clip::class);
    }

    public function getUrlAttribute()
    {
        if (! $this->storage_key) return '';

        return Storage::disk($this->storage_disk)->url($this->storage_key);
    }

    public function getDownloadUrlAttribute()
    {
        if ($this->status === 'completed') return route('posts.download', $this);

        return '';
    }
}
