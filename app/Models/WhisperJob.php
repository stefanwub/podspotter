<?php

namespace App\Models;

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
}
