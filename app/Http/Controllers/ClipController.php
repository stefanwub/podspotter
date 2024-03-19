<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClipResource;
use App\Models\Clip;
use App\Models\Team;
use Illuminate\Http\Request;

class ClipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $request->validate([
            'order_by' => [
                'required',
                'in:created_at,updated_at'
            ]
        ]);

        $query = $team->clips()->with('episode', 'episode.show');

        if ($request->get('status')) {
            $query = $query->whereIn('status', explode(',', $request->get('status')));
        }

        $query = $query->orderBy($request->get('order_by', 'desc') ?? 'updated_at', 'desc');

        return ClipResource::collection($query->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $request->validate([
            'episode_id' => [
                'required',
                'exists:episodes,id'
            ],
            'title' => [
                'nullable',
                'string'
            ],
            'start_region' => [
                'required',
                'integer',
                'min:0'
            ],
            'end_region' => [
                'required',
                'integer',
                'gt:start_region'
            ],
            'name' => [
                'required',
                'string',
                'max:255'
            ]
        ]);

        $clip = $team->clips()->create([
            'episode_id' => $request->get('episode_id'),
            'start_region' => $request->get('start_region'),
            'end_region' => $request->get('end_region'),
            'name' => $request->get('name'),
            'title' => $request->get('title'),
            'status' => 'processing'
        ]);

        return new ClipResource($clip);

    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team, Clip $clip)
    {
        $this->authorize('view', $team);

        return new ClipResource($clip);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, Clip $clip)
    {
        $this->authorize('view', $team);

        $request->validate([
            'start_region' => [
                'required',
                'integer',
                'min:0'
            ],
            'end_region' => [
                'required',
                'integer',
                'gt:start_region'
            ],
            'title' => [
                'nullable',
                'string'
            ],
            'name' => [
                'required',
                'string',
                'max:255'
            ]
        ]);

        $clip->update([
            'start_region' => $request->get('start_region'),
            'end_region' => $request->get('end_region'),
            'name' => $request->get('name'),
            'title' =>  $request->get('title')
        ]);       

        return new ClipResource($clip);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, Clip $clip)
    {
        $this->authorize('view', $team);

        $clip->posts()->delete();

        $clip->delete();

        return response('', 204);
    }
}
