<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
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
            'query' => $this->query,
            'include_categories' => CategoryResource::collection($this->includeCategories()),
            'exclude_categories' => CategoryResource::collection($this->excludeCategories()),
            'include_shows' => ShowResource::collection($this->includeShows()),
            'exclude_shows' => ShowResource::collection($this->excludeShows()),
            'alerts' => $this->alerts,
            'older_episode_alerts' => $this->older_episode_alerts
        ];
    }
}
