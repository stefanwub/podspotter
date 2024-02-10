<?php

namespace App\Jobs;

use App\Models\WhisperJob;
use App\Services\LocalWhisperService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunLocalWhisper implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public WhisperJob $whisperJob)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (! $this->whisperJob->localQueueIsEmpty()) return;

            $this->whisperJob->update([
                'status' => 'running'
            ]);

            $output = LocalWhisperService::transcribe($this->whisperJob->episode->enclosure_url);

            if ($output['error']) {
                $this->whisperJob->update([
                    'status' => 'failed'
                ]);

                return;
            }

            $this->whisperJob->update([
                'status' => 'succeeded',
                // 'succeeded_at' => now(),
                // 'execution_time' => round($output['execution_time'], 3) * 1000,
                'text' => $output['text'],
                'chunks' => $output['chunks'],
            ]);

            $this->whisperJob->runNextLocal();
        } catch(Exception $e) {
            $this->whisperJob->update([
                'status' => 'failed'
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
