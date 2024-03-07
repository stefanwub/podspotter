<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResultResource;
use App\Http\Resources\SearchResource;
use App\Models\Search;
use App\Models\Team;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team, Request $request)
    {
        $request->validate([
            'saved' => 'nullable|boolean'
        ]);

        $this->authorize('view', $team);

        return SearchResource::collection($request->get('saved') ? $team->savedSearches()->with('categories', 'shows')->paginate() : $team->searches()->with('categories', 'shows')->orderBy('created_at', 'desc')->paginate($request->get('per_page') ?? 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Team $team, Request $request)
    {
        $this->authorize('update', $team);

        $request->validate([
            'query' => [
                'required',
                'string'
            ],
            'order_by' => [
                'nullable',
                'in:published_at,indexed_at'
            ],
            'include_categories' => [
                'nullable',
                'array'
            ],
            'include_categories.*' => [
                'exists:categories,id'
            ],
            'exclude_categories' => [
                'nullable',
                'array'
            ],
            'exclude_categories.*' => [
                'exists:categories,id'
            ],
            'include_shows' => [
                'nullable',
                'array'
            ],
            'include_shows.*' => [
                'exists:shows,id'
            ],
            'exclude_shows' => [
                'nullable',
                'array'
            ],
            'exclude_shows.*' => [
                'exists:shows,id'
            ]
        ]);

        // if ($search = $team->searches()->with('shows', 'categories')->where('query', $request->get('query'))->first()) {
        //     $search->touch();
        // } else {
            $search = $team->searches()->create([
                'query' => $request->get('query'),
                'order_by' => $request->get('order_by'),
                'alerts' => true,
                'saved_at' => now()
            ]);
        // }

        $search->syncByRequest($request);

        // $search->shows()->sync(['9b4b5f19-acb1-4de5-8737-ca3b0f6a3540' => ['include' => false], '9b63d5d3-cdca-4509-b420-6ebe7e74e65a' => ['include' => false]]);

        return new SearchResource($search);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team, Search $search, Request $request)
    {
        $this->authorize('view', $team);

        $search->load('results');

        return new SearchResource($search);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, Search $search)
    {
        $this->authorize('update', $team);

        $request->validate([
            'query' => [
                'required',
                'string'
            ],
            'order_by' => [
                'nullable',
                'in:published_at,indexed_at'
            ],
            'include_categories' => [
                'nullable',
                'array'
            ],
            'include_categories.*' => [
                'exists:categories,id'
            ],
            'exclude_categories' => [
                'nullable',
                'array'
            ],
            'exclude_categories.*' => [
                'exists:categories,id'
            ],
            'include_shows' => [
                'nullable',
                'array'
            ],
            'include_shows.*' => [
                'exists:shows,id'
            ],
            'exclude_shows' => [
                'nullable',
                'array'
            ],
            'exclude_shows.*' => [
                'exists:shows,id'
            ]
        ]);

        $search->update([
            'query' => $request->get('query'),
            'order_by' => $request->get('order_by')
            // 'alerts' => $request->get('alerts') ?? false,
            // 'older_episode_alerts' => $request->get('older_episode_alerts') ?? false
        ]);

        $search->syncByRequest($request);

        return new SearchResource($search);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, Search $search)
    {
        $this->authorize('delete', $team);

        $search->results()->delete();

        $search->categories()->sync([]);

        $search->shows()->sync([]);

        $search->delete();

        return response()->json([], 204);
    }
}
