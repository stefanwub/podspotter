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

        $gpus = config('services.gpus');

        foreach ($gpus as $instance => $server) {
            foreach ($server['gpus'] as $gpu) {
                dispatch(function () use ($server, $gpu) {
                    $i = 0;

                    while($i <= 5) {

                        if (
                            ! WhisperJob::whereIn('status', ['running', 'starting'])
                                ->where('server', $server['host'])
                                ->where('gpu', $gpu)
                                ->count()
                        ) {
                            WhisperJob::transcribeNext($server['host'], $gpu);

                            break;
                        }

                        sleep(10);

                        $i++;

                    }
                })->onQueue($instance . '-' . $gpu );
            }
        } 
    }
}
