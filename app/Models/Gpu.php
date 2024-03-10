<?php

namespace App\Models;

use App\Jobs\CreateGpuInstance;
use App\Jobs\RestartGpuInstance;
use App\Jobs\RunWhisperJobOnGpu;
use App\Jobs\StartGpuInstance;
use App\Jobs\StopGpuInstance;
use App\Services\GoogleCloudComputeService;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Throwable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gpu extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected static function booted()
    {
        self::created(function (Gpu $gpu): void {
            CreateGpuInstance::dispatch($gpu)->onQueue('gpus');
        });

        self::saved(function (Gpu $gpu): void {
            if ($gpu->status === 'terminated' && $gpu->getOriginal('status') !== 'terminated') {
                RestartGpuInstance::dispatch($gpu)->onQueue('gpus');
            }

            if ($gpu->status === 'stopping' && $gpu->getOriginal('status') !== 'stopping') {
               StopGpuInstance::dispatch($gpu)->onQueue('gpus');
            }

            if ($gpu->status === 'starting' && $gpu->getOriginal('status') !== 'starting') {
               StartGpuInstance::dispatch($gpu)->onQueue('gpus');
            }

            if ($gpu->status === 'restart_failed' && $gpu->getOriginal('status') !== 'restart_failed') {
               StartGpuInstance::dispatch($gpu)->onQueue('gpus')->delay(30);
            }

            if ($gpu->status === 'start_failed' && $gpu->getOriginal('status') !== 'start_failed') {
                StartGpuInstance::dispatch($gpu, 'start_failed_again')->onQueue('gpus')->delay(30);
            }
        });
    }


    public function whisperJobs() : HasMany
    {
        return $this->hasMany(WhisperJob::class);
    }

    public function markGpuAsTerminated()
    {
        if ($this->status === 'active') { 
            $this->status = 'terminated';
            $this->save();
        }
    }

    public function createInstance()
    {
        return GoogleCloudComputeService::make()->createInstance($this);
    }

    public function getInstance()
    {
        return GoogleCloudComputeService::make()->getInstance($this->external_name, $this->zone);
    }

    public function stopInstance()
    {
        return GoogleCloudComputeService::make()->stopInstance($this->external_name, $this->zone);
    }

    public function startInstance()
    {
        return GoogleCloudComputeService::make()->startInstance($this->external_name, $this->zone);
    }

    public function deleteInstance()
    {
        return GoogleCloudComputeService::make()->deleteInstance($this->external_name, $this->zone);
    }

    public function instanceIsRunning()
    {
        $instance = $this->getInstance();

        return isset($instance['status']) && $instance['status'] === 'RUNNING';
    }

    public function createNewBatch()
    {
        if ($this->status !== 'active') return;

        if (! $this->readyForNewBatch()) return;

        $jobs = [];

        $whisperJobs = WhisperJob::where("whisper_jobs.status", "queued")
            ->whereNull("whisper_jobs.gpu_id")
            ->join("episodes", "whisper_jobs.episode_id", "=", "episodes.id")
            ->join("shows", "episodes.show_id", "=", "shows.id")
            ->where("shows.active", 1)
            ->orderBy("shows.priority", "desc")
            ->orderBy("episodes.published_at", "desc")
            ->select("whisper_jobs.*")
            ->limit(10)
            ->get();

        if ($whisperJobs->isEmpty()) return;

        foreach($whisperJobs as $whisperJob) {
            $whisperJob->update(['gpu_id' => $this->id, 'status' => 'batched']);
            $jobs[] = new RunWhisperJobOnGpu($whisperJob, $this);
        }

        $batch = Bus::batch($jobs)->progress(function (Batch $batch) {
            // A single job has completed successfully...
        })->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
            $gpu = Gpu::where('name', $batch->name)->first();

            if ($gpu) {
                $gpu->whisperJobs()->whereIn('status', ['batched', 'running', 'starting'])->update([
                    'gpu_id' => null,
                    'status' => 'queued'
                ]);
            }
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->name($this->name)->onQueue($this->queue)->dispatch();

        $this->current_job_batch_id = $batch->id;
        $this->save();

        return $batch;
    }

    public function getBatch()
    {
        if (! $this->current_job_batch_id) return null;

        return Bus::findBatch($this->current_job_batch_id);
    }

    public function readyForNewBatch()
    {
        $batch = $this->getBatch();

        if (! $batch) return true;

        return $batch->pendingJobs < 3;
    }
}
