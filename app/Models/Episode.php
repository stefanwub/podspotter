<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class Episode extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'transcribed_at' => 'datetime',
        'published_at' => 'datetime'
    ];

    protected static function booted()
    {
        self::creating(function (Episode $episode): void {
            $episode->status = 'imported';
        });
    }

    public function whisperJob() : HasOne
    {
        return $this->hasOne(WhisperJob::class);
    }

    public function show() : BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function sections() : HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function createWhisperJob()
    {
        if ($this->whisperJob?->status === 'queued' || $this->whisperJob?->status === 'succeeded') {
            return;
        }

        $this->whisperJob()->create([
            'status' => 'queued'
        ]);
        
        $this->update([
            'status' => 'queued'
        ]);
    }

    public function createSections()
    {
        if (! $this->whisperJob) {
            $this->createWhisperJob();
            return;
        }

        if ($this->whisperJob->status !== 'succeeded') return;

        if ($this->sections->isNotEmpty()) return;

        $duration = 0;
        $start = 0;
        $end = 0;
        $text = '';

        $sections = [];

        foreach ($this->whisperJob->chunks as $chunk) {
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
                    'text' => $text,
                    'start' => $start,
                    'end' => $end,
                    'duration' => $duration
                ];
                
                $this->createSection($text, $start, $end);

                $duration = 0;
                $text = '';
            }
        }

        if ($text) {
            $sections[] = [
                'text' => $text,
                'start' => $start,
                'end' => $end,
                'duration' => $duration
            ];
            
            $this->createSection($text, $start, $end);
        }

        $this->update([
            'status' => 'transcribed',
            'transcribed_at' => now()
        ]);

        $this->whisperJob->update([
            'status' => 'completed'
        ]);
    }

    public function createSection($text, $start, $end)
    {
        $embedding = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $text
        ]);

        $this->sections()->create([
            'content' => $text,
            'start_time' => $start,
            'end_time' => $end,
            'embedding' => $embedding->embeddings[0]->embedding
        ]); 
    }
}
