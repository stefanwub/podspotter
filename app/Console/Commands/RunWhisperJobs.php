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

        $servers = [
            '34.32.251.14', // instance-5
            '34.141.245.138' // instance-6
        ];

        foreach ($servers as $index => $server) {
            dispatch(function () use ($server) {
                $i = 0;

                while($i <= 3) {

                    if (
                        ! WhisperJob::whereIn('status', ['running', 'starting'])
                            ->where('server', $server)
                            ->count()
                    ) {
                        WhisperJob::transcribeNext($server);

                        break;
                    }
                    sleep(10);

                    $i++;

                }
            })->onQueue('whisper-job-check-' . $index );
        } 
    }
}
