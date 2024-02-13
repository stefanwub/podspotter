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
    protected $signature = 'app:create-episode-whisper-jobs {--limit=100} {--from_published_at=}';

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
        $query = Episode::whereNotIn('status', ['queued', 'transcribed', 'transcribing']);

        if ($this->option('from_published_at')) {
            $query->where('published_at', '>', Carbon::parse($this->option('from_published_at')));
        } else {
            // $query->where('published_at', '>', Carbon::parse('last year'));
        }

        $episodes = $query->limit($this->option('limit'))->orderBy('published_at', 'DESC')->get();

        foreach ($episodes as $episode) {
            $episode->createWhisperJob();
        }
    }
}
