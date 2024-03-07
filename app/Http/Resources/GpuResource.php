<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GpuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ip' => $this->ip,
            'status' => $this->status,
            'machine_image' => $this->machine_image,
            'zone' => $this->zone,
            'instance' => $this->instance,
            'queue' => $this->queue,
            'current_job_batch_id' => $this->current_job_batch_id,
            'error_message' => $this->error_message
        ];
    }
}
