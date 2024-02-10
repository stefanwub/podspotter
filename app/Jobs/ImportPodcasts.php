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

class ImportPodcasts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    protected function import($podcasts)
    {
        foreach ($podcasts as $podcast) {
            ImportPodcastShow::dispatch($podcast);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // itunes
        $podcasts = ScrapeChartService::make()->scrapePages('https://chartable.com/charts/itunes/nl-all-podcasts-podcasts');

        $this->import($podcasts);

        // spotify
        $podcasts = ScrapeChartService::make()->scrapePages('https://chartable.com/charts/spotify/netherlands-top-podcasts');

        $this->import($podcasts);
    }
}
