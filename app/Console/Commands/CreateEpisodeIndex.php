<?php

namespace App\Console\Commands;

use App\Jobs\IndexEpisode;
use App\Models\Episode;
use Illuminate\Console\Command;

class CreateEpisodeIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-episode-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $episodes = Episode::whereIn('status', ['transcribing', 'transcribed'])->limit(100)->get();

        foreach ($episodes as $episode) {
            IndexEpisode::dispatch($episode)->onQueue('episodes');
        }
    }
}
