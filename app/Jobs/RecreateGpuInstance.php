<?php

namespace App\Jobs;

use App\Models\Gpu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecreateGpuInstance implements ShouldQueue
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
        if ($this->gpu->status === 'active') return;

        $operationResponse = $this->gpu->deleteInstance();

        $operationResponse->pollUntilComplete();

        if ($operationResponse->operationSucceeded()) {
            $this->gpu->update([
                'status' => 'creating'
            ]);

            CreateGpuInstance::dispatch();
        } else {
            $this->gpu->update([
                'status' => 'failed',
                'error_status' => 'Deleting instance for recreation failed.'
            ]);

            throw new Exception('Deleting gpu instance ' . $this->gpu->name . ' for recreation failed.');
        }
    }
}
