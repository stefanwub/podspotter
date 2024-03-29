<?php

namespace App\Models;

use App\Jobs\UpsertSectionsToVectorDb;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use OpenAI\Laravel\Facades\OpenAI;

class Episode extends Model
{
    use HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    protected $casts = [
        'transcribed_at' => 'datetime',
        'published_at' => 'datetime',
        'indexed_at' => 'datetime',
        'embedded_at' => 'datetime'
    ];

    protected static function booted()
    {
        self::creating(function (Episode $episode): void {
            $episode->status = 'imported';
        });

        self::saving(function (Episode $episode): void {
            if ($episode->status === 'indexed' && $episode->getOriginal('status') !== 'indexed') {
                $episode->indexed_at = now();
            }
        });
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('whisperJobs');
    }

    public function isIndexable()
    {
        return $this->whisperJobs->whereIn('status', ['completed', 'succeeded'])->first() ? true : false;
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'indexed';
    }

    public function toSearchableArray()
    {
        $whisperJob = $this->whisperJobs->whereIn('status', ['completed', 'succeeded'])->first();
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => strip_tags($this->description),
            'show_id' => $this->show_id,
            'medium' => $this->medium,
            'categories' => $this->show->categories->pluck('id'),
            'published_at' => $this->published_at->timestamp,
            'indexed_at' => $this->indexed_at ? $this->indexed_at->timestamp : $this->published_at->timestamp,
            'enclosure_url' => $this->enclosure_url,
            'show' => [
                'id' => $this->show?->id,
                'title' => $this->show?->title,
                'image_url' => $this->show->image_url
            ],
            'sections' => $this->toSections($whisperJob)
            // 'chunks' => collect($whisperJob?->chunks)->map(function($c) {
            //     return [
            //         't' => $c['timestamp'],
            //         'text' => $c['text']
            //     ];
            // })
        ];
    }

    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('meilisearch');
    }

    public function whisperJob() : HasOne
    {
        return $this->hasOne(WhisperJob::class);
    }

    public function whisperJobs() : HasMany
    {
        return $this->hasMany(WhisperJob::class);
    }

    public function show() : BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function sections() : HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function mediaFile() : HasOne
    {
        return $this->hasOne(MediaFile::class);
    }

    public function clips() : HasMany
    {
        return $this->hasMany(Clip::class);
    }

    public function createWhisperJob()
    {
        if ($this->whisperJob) {
            if ($this->whisperJob?->status !== 'failed') {
                return;
            }
        }

        $this->whisperJob()->create([
            'status' => 'queued'
        ]);
        
        $this->update([
            'status' => 'queued'
        ]);
    }

    public function toSections(WhisperJob $whisperJob = null)
    {
        if (! $whisperJob) return [];

        $duration = 0;
        $start = 0;
        $end = 0;
        $text = '';

        $sections = [];

        foreach ($whisperJob->chunks as $chunk) {
            $startChunk = round($chunk['timestamp'][0], 3) * 1000;
            $endChunk = round($chunk['timestamp'][1], 3) * 1000;

            if (! $duration) {
                $start = $startChunk;
            }

            $end = $endChunk;

            $duration += ($endChunk - $startChunk);

            $text .= $chunk['text'];

            if ($duration >= 60000) {
                $sections[] = [
                    't' => $text,
                    's' => $start,
                    'e' => $end,
                ];
                
                $duration = 0;
                $text = '';
            }
        }

        if ($text) {
            $sections[] = [
                't' => $text,
                's' => $start,
                'e' => $end
            ];
        }

        return $sections;
    }

    public function getSectionsForEmbedding(WhisperJob $whisperJob)
    {
        return collect($this->toSections($whisperJob))->map(function ($section, $index) {
            return [
                'id' => $this->id . '_' . $index,
                'show' => [
                    'title' => $this->show?->title,
                    'author' => $this->show?->author,
                ],
                'categories' => $this->show?->categories->pluck('id'),
                'title' => $this->title,
                'show_id' => $this->show_id,
                'episode_id' => $this->id,
                'medium' => $this->show?->medium,
                'published_at' => $this->published_at->timestamp,
                'indexed_at' => $this->indexed_at ? $this->indexed_at->timestamp : $this->published_at->timestamp,
                'text' => $section['t'],
                'start' => $section['s'],
                'end' => $section['e']
            ];
        });
    }
}
