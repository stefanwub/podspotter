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
            ...parent::toArray($request),
            'episode' => [
                'id' => $this->episode?->id,
                'title' => $this->episode?->title,
                'enclosure_url' => $this->episode?->enclosure_url,
                'show' => $this->episode?->show ? [
                    'id' => $this->episode?->show->id,
                    'title' => $this->episode?->show->title,
                    'author' => $this->episode->show->author,
                    'image_url' => $this->episode?->show->image_url,
                ] : null
            ]
        ];
    }
}
