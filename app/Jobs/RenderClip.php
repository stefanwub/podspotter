<?php

namespace App\Jobs;

use App\Models\Clip;
use Bus;
use Exception;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Storage;
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
        if ($this->clip->episode?->medium === 0 && ! $this->clip->episode?->mediaFile) return;

        try {
            $start = \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clip->start_region / 1000);
            $duration = \FFMpeg\Coordinate\TimeCode::fromSeconds(($this->clip->end_region / 1000) - ($this->clip->start_region / 1000));

            if ($this->clip->episode->medium === 1) {
                $clipFilter = new \FFMpeg\Filters\Video\ClipFilter($start, $duration);

                $episodeLocalFile = "episodes/" . $this->clip->episode_id . ".mp4";

                if (! Storage::disk('local')->exists($episodeLocalFile)) {
                    if ($this->clip->status === 'downloading') {
                        throw new Exception('Downloading failed');
                    }

                    Bus::chain([
                       new DownloadYoutubeVideo($this->clip->episode->enclosure_url, 'episodes', $this->clip->episode_id . ".mp4"),
                       new RenderClip($this->clip)
                    ])->onQueue('video')->dispatch();

                    $this->clip->update([
                        'status' => 'downloading'
                    ]);

                    return;
                }

                $this->clip->update([
                    'status' => 'rendering'
                ]);

                $clipFilename = 'clips/' . $this->clip->id . ".mp4";

                FFMpeg::fromDisk('local')
                    ->open($episodeLocalFile)
                    ->addFilter($clipFilter)
                    ->export()
                    ->toDisk(config('filesystems.default'))
                    ->inFormat(new \FFMpeg\Format\Video\X264)
                    ->save($clipFilename);

                $this->clip->update([
                    'status' => 'completed',
                    'storage_disk' => config('filesystems.default'),
                    'storage_key' => $clipFilename
                ]);

                return;
            }

            $mediaFile = $this->clip->episode?->mediaFile;

            if ($mediaFile?->audio_storage_key) {

                $this->clip->update([
                    'status' => 'rendering'
                ]);

                $clipFilter = new \FFMpeg\Filters\Audio\AudioClipFilter($start, $duration);

                $filename = "clips/" . $this->clip->id . ".mp3";

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

                return;
            }

            $this->clip->update([
                'status' => 'failed'
            ]);

        } catch(Exception $e) {
            $this->clip->update([
                'status' => 'failed'
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
