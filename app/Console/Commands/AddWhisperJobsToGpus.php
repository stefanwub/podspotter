<?php

namespace App\Console\Commands;

use App\Models\Gpu;
use App\Models\WhisperJob;
use Cache;
use Illuminate\Console\Command;

class AddWhisperJobsToGpus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-whisper-jobs-to-gpus';

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
        // $perBatch = 10;

        $gpus = Gpu::where('status', 'active')->get();

        // $gpusCount = $gpus->count();

        // $limit = $gpusCount * $perBatch;

        // $whisperJobs = WhisperJob::where('status', 'queued')->whereNull('gpu_id')->limit($limit)->get();

        // $perBatch = $whisperJobs->count() / $gpusCount;

        // $offset = 0;

        foreach ($gpus as $gpu) {
            $gpu->createNewBatch();

            // $offset = $offset+$perBatch;
        }
    }
}
