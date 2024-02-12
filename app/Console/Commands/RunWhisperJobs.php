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

        $servers = [
            '34.32.251.14', // instance-5
            '34.141.245.138' // instance-6
        ];

        $whisperJobs = WhisperJob::where('status', 'queued')->limit(2)->get();

        $index = 0;

        foreach ($whisperJobs as $whisperJob) {
            if ($whisperJob) {
                $whisperJob->update([
                    'server' => $servers[$index],
                    'status' => 'starting'
                ]);

                $whisperJob->episode?->update([
                    'status' => 'transcribing',
                    'enclosure_url' => strtok($whisperJob->episode->enclosure_url, "?")
                ]);

                LocalWhisperService::transcribe($whisperJob);
            }

            $index++;
        } 
    }
}
