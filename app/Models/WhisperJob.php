<?php

namespace App\Models;

use App\Services\LocalWhisperService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public static function transcribeNext($server, $gpu)
    {
        $whisperJob = WhisperJob::where('status', 'queued')->first();

        if ($whisperJob) {
            $whisperJob->update([
                'server' => $server,
                'gpu' => $gpu,
                'status' => 'starting'
            ]);

            $whisperJob->episode?->update([
                'status' => 'transcribing',
                'enclosure_url' => strtok($whisperJob->episode->enclosure_url, "?")
            ]);

            LocalWhisperService::transcribe($whisperJob);
        }
    }
}
