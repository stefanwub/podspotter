<?php

namespace App\Jobs;

use App\Models\Clip;
use Exception;
use FFMpeg\Format\Audio\Mp3;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Storage;
use Str;

class GenerateClipSubtitles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Clip $clip)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->clip->storage_key) return;

        $audioPath = $this->clip->storage_key;

        if (Str::endsWith($audioPath, '.mp4')) {
            $audioPath = Str::replace('.mp4', '.mp3', $audioPath);

            if (! Storage::disk($this->clip->storage_disk)->exists($audioPath)) {
                FFMpeg::fromDisk($this->clip->storage_disk)
                    ->open($this->clip->storage_key)
                    ->export()
                    ->toDisk($this->clip->storage_disk)
                    ->inFormat(new Mp3)
                    ->save($audioPath);
            }
        }

        $response = Http::withHeader('Authorization', 'Token ' . config('services.replicate.api_token'))
            ->post('https://api.replicate.com/v1/predictions', [
                'version' => '3ab86df6c8f54c11309d4d1f930ac292bad43ace52d10c80d87eb258b3c9f79c',
                'input' => [
                    'task' => 'transcribe',
                    'audio' => Storage::disk($this->clip->storage_disk)->url($audioPath),
                    'language' => 'dutch',
                    'timestamp' => $this->clip->subtitle_timestamp ?? 'chunk'
                ],
                'webhook' => route('clip.subtitles-webhook', $this->clip)
            ]);

        if ($response->successful()) {
            return;
        }

        throw new Exception($response->body());
    }
}
