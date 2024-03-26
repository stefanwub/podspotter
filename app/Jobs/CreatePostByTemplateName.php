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

        if ($this->post->template_name === 'video-petjeaf-insta') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1080)
                ->addClipVideo()
                ->addText($this->post->title, 'white', 26, 72, 100, '#510fa8')
                ->addSubtitles('#FFFFFF', '#000000')
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'video-petjeaf-insta-light') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1080)
                ->addClipVideo()
                ->addText($this->post->title, '#510fa8', 26, 72, 100, 'white')
                ->addSubtitles('#FFFFFF', '#000000')
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'video-petjeaf-reel') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1920)
                ->addClipVideo()
                ->addText($this->post->title, 'white', 26, 72, 100, '#510fa8')
                ->addSubtitles('#FFFFFF', '#000000')
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'video-petjeaf-reel-light') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1920)
                ->addClipVideo()
                ->addText($this->post->title, '#510fa8', 26, 72, 100, 'white')
                ->addSubtitles('#FFFFFF', '#000000')
                ->save($path);

            $thumbPath = $this->addThumbnail($path, $this->post->clip?->storage_disk);

            $this->post->update([
                'status' => 'completed',
                'thumbnail_storage_key' => $thumbPath
            ]);
        }

        if ($this->post->template_name === 'petjeaf-insta-purple') {
            $this->post->update([
                'storage_key' => $path,
                'storage_disk' => $this->post->clip?->storage_disk,
                'status' => 'rendering'
            ]);

            ClipPostService::clip($this->post->clip)
                ->size(1080, 1080)
                ->addBackground('gradient:#510fa8-#4338ca')
                ->addWaveform('#c7d2fe', 'cline', 1080, 480, 0, 350)
                ->addShowImage($this->post->clip?->episode?->show, 480, 480, 100, '(W-w)/2', 350)
                ->addText($this->post->title, 'white', 26, 72, 100)
                ->addSubtitles('#FFFFFF', '#000000')
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
                ->addWaveform('#510fa8', 'cline', 1080, 480, 0, 350)
                ->addShowImage($this->post->clip?->episode?->show, 480, 480, 100, '(W-w)/2', 350)
                ->addText($this->post->title, 'Black', 26, 72, 100)
                ->addSubtitles('#FFFFFF', '#000000')
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
                ->addWaveform('#c7d2fe', 'cline', 1080, 480, 0, '(H-h)/2')
                ->addShowImage($this->post->clip?->episode?->show, 480, 480, 100, '(W-w)/2', '(H-h)/2')
                ->addText($this->post->title, 'white', 28, 72, 400)
                ->addSubtitles()
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
