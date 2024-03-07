<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
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
            'epsiode_id' => $this->episode_id,
            'title' => $this->episode?->title,
            'published_at' => $this->episode?->published_at,
            'categories' => $this->episode?->show?->categories->pluck('id'),
            'show' => $this->episode?->show ? [
                'id' => $this->episode?->show->id,
                'title' => $this->episode?->show->title,
                'image_url' => $this->episode?->show->image_url
            ] : null,
            'description' => $this->episode?->description,
            'enclosure_url' => $this->episode?->enclosure_url,
            'sections' => $this->sections
        ];
    }
}
