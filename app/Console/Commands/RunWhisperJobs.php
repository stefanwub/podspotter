<?php

namespace App\Console\Commands;

use App\Models\WhisperJob;
use App\Services\LocalWhisperService;
use Cache;
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
    protected $description = 'Run next whisper job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Cache::has('pause_whisper_jobs')) return;

        if (WhisperJob::whereIn('status', ['running', 'starting'])->count()) return;

        $whisperJob = WhisperJob::where('status', 'queued')->first();

        if ($whisperJob) {
            $whisperJob->update([
                'status' => 'starting'
            ]);

            $whisperJob->episode?->update([
                'status' => 'transcribing',
                'enclosure_url' => strtok($whisperJob->episode->enclosure_url, "?")
            ]);

            LocalWhisperService::transcribe($whisperJob);
        }
    }
}
