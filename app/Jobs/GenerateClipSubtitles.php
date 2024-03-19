<?php

namespace App\Jobs;

use App\Models\Clip;
use Exception;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $response = Http::withHeader('Authorization', 'Token ' . config('services.replicate.api_token'))
            ->post('https://api.replicate.com/v1/predictions', [
                'version' => '3ab86df6c8f54c11309d4d1f930ac292bad43ace52d10c80d87eb258b3c9f79c',
                'input' => [
                    'task' => 'transcribe',
                    'audio' => $this->clip->url,
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
