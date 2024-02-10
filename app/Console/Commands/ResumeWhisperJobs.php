<?php

namespace App\Console\Commands;

use Cache;
use Illuminate\Console\Command;

class ResumeWhisperJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:resume-whisper-jobs';

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
        Cache::delete('pause_whisper_jobs');
    }
}
