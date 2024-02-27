<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Episode $episode)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $this->episode->sections()->delete();

        $this->episode->whisperJobs()->delete();

        $this->episode->delete();
    }
}
