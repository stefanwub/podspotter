<?php

namespace App\Console\Commands;

use App\Models\WhisperJob;
use Cache;
use Illuminate\Console\Command;

class PauseWhisperJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pause-whisper-jobs';

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
        WhisperJob::where('status', 'running')->update([
            'status' => 'queued'
        ]);
        
        Cache::add('pause_whisper_jobs', true);
    }
}
