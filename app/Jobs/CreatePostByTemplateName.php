<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\ClipPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Str;

class CreatePostByTemplateName implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Post $post)
    {
        //
    }

    protected function addThumbnail($path, $disk)
    {
        $thumbPath = 'posts/thumbnails/' . Str::uuid() . '.jpg';
        
        FFMpeg::fromDisk($disk)
            ->open($path)
            ->getFrameFromSeconds(2)
            ->export()
            ->toDisk($disk)
            ->save($thumbPath);

        return $thumbPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $path = 'posts/' . Str::uuid() . '.mp4';

        if ($this->post->template_name === 'petjeaf-insta-purple') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1080)
                ->addBackground('gradient:#510fa8-#4338ca')
                ->addWaveform('White', 'line', 1080, 400, 0, 600)
                ->addShowImage($this->post->clip?->episode?->show, 750, 750)
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'petjeaf-insta-light') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1080)
                ->addBackground('gradient:#cccccc-#ffffff')
                ->addWaveform('#4338ca', 'line', 1080, 400, 0, 600)
                ->addShowImage($this->post->clip?->episode?->show, 750, 750)
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'petjeaf-reel-purple') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1920)
                ->addBackground('gradient:#510fa8-#4338ca')
                ->addWaveform('White', 'line', 1080, 400)
                ->addShowImage($this->post->clip?->episode?->show, 750, 750)
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'petjeaf-reel-light') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1920)
                ->addBackground('gradient:#cccccc-#ffffff')
                ->addWaveform('#4338ca', 'line', 1080, 400)
                ->addShowImage($this->post->clip?->episode?->show, 750, 750)
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }
    }
}
