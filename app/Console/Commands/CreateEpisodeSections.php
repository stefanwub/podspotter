<?php

namespace App\Console\Commands;

use App\Models\WhisperJob;
use Illuminate\Console\Command;

class CreateEpisodeSections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-episode-sections';

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
        $whisperJobs = WhisperJob::where('status', 'succeeded')->get();

        foreach ($whisperJobs as $whisperJob) {
            dispatch(function () use ($whisperJob) {
                $whisperJob->episode?->createSections();
            });
        }
    }
}
