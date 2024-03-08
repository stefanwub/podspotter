<?php

namespace App\Jobs;

use App\Models\Gpu;
use App\Models\WhisperJob;
use App\Services\LocalWhisperService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class RunWhisperJobOnGpu implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected WhisperJob $whisperJob, protected Gpu $gpu)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->whisperJob->status !== 'batched') return;

        if ($this->whisperJob->gpu_id !== $this->gpu->id) return;

        if ($this->batch()->canceled()) {
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);
            return;
        }

        if ($this->gpu->whisperJobs()->whereIn('status', ['starting', 'running'])->first()) {
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);
            return;
        }

        if ($this->gpu->status !== 'active') {
            $this->batch()->cancel();
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);       
        }

        try {
            if (! $this->gpu->instanceIsRunning()) {
                $this->batch()->cancel();
                $this->whisperJob->update([
                    'gpu_id' => null,
                    'status' => 'queued'
                ]);
                $this->gpu->markGpuAsTerminated();
                return;
            }

            $this->whisperJob->update([
                'gpu' => 0,
                'status' => 'starting'
            ]);

            $this->whisperJob->episode?->update([
                'status' => 'transcribing',
                'enclosure_url' => strtok($this->whisperJob->episode->enclosure_url, "?")
            ]);
            

            LocalWhisperService::transcribeOnGpu($this->whisperJob);
        } catch(Exception $e) {
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);
            throw new Exception($e->getMessage());
        }

        $this->whisperJob->refresh();

        if ($this->whisperJob->status === 'starting') {
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);
            throw new Exception('Whisper job ' . $this->whisperJob->id . ' status is still starting');
        }

        if ($this->whisperJob->status === 'running') {
            $this->whisperJob->update([
                'gpu_id' => null,
                'status' => 'queued'
            ]);
            throw new Exception('Whisper job ' . $this->whisperJob->id . ' status is still running');
        }
    }
}
