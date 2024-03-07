<?php

namespace App\Jobs;

use App\Models\Gpu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteGpuInstance implements ShouldQueue
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
        if ($this->gpu->status !== 'deleting') return;

        $operationResponse = $this->gpu->deleteInstance();

        $operationResponse->pollUntilComplete();

        if ($operationResponse->operationSucceeded()) {
            $this->gpu->delete();
        } else {
            $error = $operationResponse->getError();

            $this->gpu->update([
                'status' => 'deleting_failed',
                'error_message' => $error->getMessage()
            ]);

            throw new Exception($error->getMessage());
        }
    }
}
