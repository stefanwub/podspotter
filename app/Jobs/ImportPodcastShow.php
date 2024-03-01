<?php

namespace App\Jobs;

use App\Models\Show;
use App\Services\PodcastIndexService;
use App\Services\ScrapeChartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportPodcastShow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $podcast)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->podcast['title']) return;
        
        $show = Show::where('title', $this->podcast['title'])->first();

        if ($show) return;

        if ($this->podcast['link']) {
            $rssFeed = ScrapeChartService::make()->getRssFeed($this->podcast['link']);

            $show = Show::where('feed_url', $rssFeed)->first();

            if ($show) return;

            $show = Show::create([
                'title' => $this->podcast['title'],
                'image_url' => $this->podcast['image'],
                'medium' => 'podcast',
                'author' => $this->podcast['author'],
                'ranking' => $this->podcast['rank'],
                'feed_url' => $rssFeed
            ]);
            
            $show->import();
        } else {
            $results = PodcastIndexService::make()->searchByTitle($this->podcast['title']);

            if (count($results->feeds)) {
                $result = $results->feeds[0];

                if (! in_array($result->language, ['nl', 'nl-nl'])) return;

                $show = Show::where('feed_url', $result->url)->first();

                if ($show) return;

                $show = Show::where('podcast_index_id', $result->id)->first();

                if ($show) return;

                $show = Show::create([
                    'title' => $result->title,
                    'podcast_index_id' => $result->id,
                    'feed_url' => $result->url,
                    'guid' => $result->podcastGuid,
                    'medium' => 'podcast',
                    'image_url' => $result->artwork,
                    'author' => $result->author,
                    'language' => $result->language,
                    'description' => $result->description,
                    'ranking' => $this->podcast['rank']
                ]);
          
                $show->import();
            }  
        }
    }
}
