<?php

namespace App\Jobs;

use App\Models\Gpu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateGpuInstance implements ShouldQueue
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
        $this->gpu->createInstance();
    }
}
