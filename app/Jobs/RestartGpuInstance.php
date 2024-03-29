<?php

namespace App\Jobs;

use App\Models\Gpu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RestartGpuInstance implements ShouldQueue
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
        if ($this->gpu->status !== 'terminated') return;

        $this->gpu->update([
            'status' => 'restarting'
        ]);

        sleep(60);

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
                'status' => 'restart_failed',
                'error_message' => $error->getMessage()
            ]);

            throw new Exception($error->getMessage());
        }
    }
}
