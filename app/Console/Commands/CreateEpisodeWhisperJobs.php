<?php

namespace App\Console\Commands;

use App\Models\Episode;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateEpisodeWhisperJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-episode-whisper-jobs {from_published_at?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a whisper job for episodes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Episode::whereIn('status', '!=', ['queued', 'transcribed']);

        if ($this->argument('from_published_at')) {
            $query->where('published_at', '>', Carbon::parse($this->argument('from_published_at')));
        }

        $episodes = $query->limit(100)->get();
        
        foreach ($episodes as $episode) {
            $episode->createWhisperJob();
        }
    }
}
