<?php

namespace App\Jobs;

use App\Models\Gpu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopGpuInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Gpu $gpu)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->gpu->status !== 'stopping') return;

        $operationResponse = $this->gpu->stopInstance();

        $operationResponse->pollUntilComplete();

        if ($operationResponse->operationSucceeded()) {
            $this->gpu->update([
                'status' => 'stopped'
            ]);
        } else {
            $error = $operationResponse->getError();
            $this->gpu->update([
                'status' => 'stopping_failed',
                'error_message' => $error->getMessage()
            ]);

            throw new Exception($error->getMessage());
        }
    }
}
