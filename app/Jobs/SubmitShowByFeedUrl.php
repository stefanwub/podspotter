<?php

namespace App\Jobs;

use App\Models\Show;
use Exception;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use willvincent\Feeds\Facades\FeedsFacade;

class SubmitShowByFeedUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $feedUrl)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        if (Show::where('feed_url', $this->feedUrl)->first()) return;

        $show = Show::create([
            'feed_url' => $this->feedUrl
        ]);

        ImportShow::dispatch($show);
    }
}
