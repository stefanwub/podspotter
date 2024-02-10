<?php

namespace App\Console\Commands;

use App\Models\WhisperJob;
use Illuminate\Console\Command;

class RunWhisperJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-whisper-jobs';

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
        WhisperJob::runNextLocal();
    }
}
