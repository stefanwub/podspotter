<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClipResource;
use App\Http\Resources\CollectionResource;
use App\Models\Clip;
use App\Models\Collection;
use Illuminate\Http\Request;

class ClipCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Collection $collection, Request $request)
    {
        if (! $collection->public) {
            $this->authorize('view', $collection->team);
        }

        if ($request->query('order_by') === 'sort') {
            $clips = $collection->clips()->orderByPivot('sort', 'desc');
        } else {
            $clips = $collection->clips()->join('episodes', 'clips.episode_id', '=', 'episodes.id') // Join clips with episodes
                ->orderBy('episodes.published_at', 'desc') // Order by the episode's published_at
                ->select('clips.*', 'clip_collection.*', 'episodes.published_at as episode_published_at');
        }

        return ClipResource::collection($clips->with(['episode', 'episode.show'])->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Collection $collection, Clip $clip)
    {
        $this->authorize('view', $collection->team);

        $latestClip = $collection->clips()->orderByPivot('sort', 'desc')->first();

        $collection->clips()->syncWithoutDetaching([$clip->id => ['sort' => $latestClip ? $latestClip->pivot->sort + 1 : 0 ]]);

        $clip->load('collections');

        return new ClipResource($clip);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection, Clip $clip)
    {
        $this->authorize('view', $collection->team);

        $collection->clips()->detach($clip->id);

        $clip->load('collections');

        return new ClipResource($clip);
    }
}
