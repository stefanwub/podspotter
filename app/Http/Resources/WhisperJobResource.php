<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhisperJobResource extends JsonResource
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
            'status' => $this->status,
            'execution_time' => $this->execution_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'error_message' => $this->error_message,
            'gpu' => $this->serverGpu ? [
                'name' => $this->serverGpu?->name,
                'id' => $this->serverGpu?->id
            ] : null,
            'episode' => [
                'id' => $this->episode?->id,
                'title' => $this->episode?->title,
                'duration' => $this->episode?->duration,
                'enclosure_url' => $this->episode?->enclosure_url,
                'show' => $this->episode?->show ? [
                    'id' => $this->episode->show->id,
                    'title' => $this->episode->show->title,
                    'image_url' => $this->episode->show->image_url
                ] : null
            ]
        ];
    }
}
