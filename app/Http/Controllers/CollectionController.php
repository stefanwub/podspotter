<?php

namespace App\Http\Controllers;

use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Team;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        return CollectionResource::collection($team->collections()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $request->validate([
            'name' => [
                'required',
                'max:255'
            ],
            'public' => [
                'boolean'
            ]
        ]);

        $collection = $team->collections()->create([
            'name' => $request->get('name'),
            'public' => $request->get('public')
        ]);

        return new CollectionResource($collection);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team, Collection $collection)
    {
        if (! $collection->public) {
            $this->authorize('view', $team);
        }

        return new CollectionResource($collection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, Collection $collection)
    {
        $this->authorize('view', $collection->team);

        $request->validate([
            'name' => [
                'required',
                'max:255'
            ],
            'public' => [
                'boolean'
            ]
        ]);

        $collection->update([
            'name' => $request->get('name'),
            'public' => $request->get('public')
        ]);

        return new CollectionResource($collection);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, Collection $collection)
    {
        $this->authorize('delete', $team);

        $collection->clips()->sync([]);

        $collection->delete();

        return response('', 204);
    }
}
