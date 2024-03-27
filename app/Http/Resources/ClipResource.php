<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClipResource extends JsonResource
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
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'title' => $this->title,
            'start_region' => $this->start_region,
            'end_region' => $this->end_region,
            'duration' => $this->duration,
            'url' => $this->url,
            'storage_disk' => $this->storage_disk,
            'storage_key' => $this->storage_key,
            'episode_id' => $this->episode_id,
            'episode' => [
                'id' => $this->episode?->id,
                'title' => $this->episode?->title,
                'enclosure_url' => $this->episode?->enclosure_url,
                'image_url' => $this->episode?->image_url,
                'published_at' => $this->episode?->published_at,
                'show' => $this->episode?->show ? [
                    'id' => $this->episode?->show->id,
                    'title' => $this->episode?->show->title,
                    'author' => $this->episode->show->author,
                    'image_url' => $this->episode?->show->image_url,
                    'image_storage_disk' => $this->episode?->show->image_storage_disk,
                    'image_storage_key' => $this->episode?->show->image_storage_key
                ] : null
            ],
            'collections' => $this->whenLoaded('collections')
        ];
    }
}
