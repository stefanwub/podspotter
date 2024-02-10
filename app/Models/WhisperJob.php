<?php

namespace App\Models;

use App\Jobs\RunLocalWhisper;
use App\Services\LocalWhisperService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class WhisperJob extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'chunks' => 'array',
        'succeeded_at' => 'datetime',
        'local' => 'boolean'
    ];

    protected static function booted()
    {
        // self::creating(function (WhisperJob $whisperJob): void {
        //     $response = $whisperJob->start($whisperJob->episode);

        //     $whisperJob->job_id = $response->json('id');
        //     $whisperJob->status = $response->json('status');
        // });

        self::created(function (WhisperJob $whisperJob): void {
            $whisperJob->runLocal();
        });

        self::saved(function (WhisperJob $whisperJob): void {
            if ($whisperJob->getOriginal('status') != $whisperJob->status && $whisperJob->status === 'succeeded') {
                $whisperJob->episode->createSections();
            }
        });
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function updateStatus()
    {
        if ($this->status === 'succeeded') return;

        if (! $this->job_id) return;

        $response = Http::withToken(env('REPLICATE_API_TOKEN'), 'Token')->get('https://api.replicate.com/v1/predictions/' . $this->job_id);

        $this->update([
            'job_id' => $response->json('id'),
            'status' => $response->json('status'),
            'text' => $response->json('output') ? $response->json('output.text') : null,
            'chunks' => $response->json('output') ? $response->json('output.chunks') : null,
        ]);
    }

    public function start(Episode $episode)
    {
        return Http::withToken(env('REPLICATE_API_TOKEN'), 'Token')->post('https://api.replicate.com/v1/predictions', [
            'version' => 'c6433aab18b7318bbae316495f1a097fc067deef7d59dc2f33e45077ae5956c7',
            'input' => [
                'task' => 'transcribe',
                'audio' => $episode->enclosure_url,
                'language' => 'dutch',
                'timestamp' => 'chunk',
                'batch_size' => 64,
                'diarise_audio' => false
            ]
        ]);
    }

    public function localQueueIsEmpty($count = 1)
    {
        return WhisperJob::where('status', 'running')->count() < $count;
    }
    
    public function runNextLocal()
    {
        $nextWhisperJob = WhisperJob::where('status', 'queued')->first();

        if ($nextWhisperJob) {
            $nextWhisperJob->runLocal();
        }
    }

    public function runLocal()
    {
        if (! $this->localQueueIsEmpty()) return;

        RunLocalWhisper::dispatch($this);
    }
}
