<?php

namespace App\Jobs;

use App\Models\Clip;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Str;

class RenderClip implements ShouldQueue
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
        if (! $this->clip->episode?->mediaFile) return;

        $this->clip->update([
            'status' => 'rendering'
        ]);

        $mediaFile = $this->clip->episode?->mediaFile;

        $start = \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clip->start_region / 1000);
        $duration = \FFMpeg\Coordinate\TimeCode::fromSeconds(($this->clip->end_region / 1000) - ($this->clip->start_region / 1000));

        $clipFilter = new \FFMpeg\Filters\Audio\AudioClipFilter($start, $duration);

        $filename = "clips/" . Str::uuid() . ".mp3";

        FFMpeg::fromDisk($mediaFile->storage_disk)
            ->open($mediaFile->audio_storage_key)
            ->addFilter($clipFilter)
            ->export()
            ->toDisk(config('filesystems.default'))
            ->inFormat(new Mp3)
            ->save($filename);

        $this->clip->update([
            'status' => 'completed',
            'storage_disk' => config('filesystems.default'),
            'storage_key' => $filename
        ]);
    }
}
