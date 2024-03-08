<?php

namespace App\Jobs;

use App\Models\Gpu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartGpuInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Gpu $gpu, protected $failedStatus = 'start_failed')
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->gpu->status !== 'starting' || ($this->gpu->status === 'start_failed' && $this->failedStatus !== 'start_failed')) return;

        $operationResponse = $this->gpu->startInstance();

        $operationResponse->pollUntilComplete();

        if ($operationResponse->operationSucceeded()) {
            $intance = $this->gpu->getInstance();

            $this->gpu->update([
                'status' => 'active',
                'ip' => $intance['ip']
            ]);
        } else {
            $error = $operationResponse->getError();
            $this->gpu->update([
                'status' => $this->failedStatus,
                'error_message' => $error->getMessage()
            ]);

            throw new Exception($error->getMessage());
        }
    }
}
