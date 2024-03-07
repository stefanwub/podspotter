<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResultResource;
use App\Models\Result;
use App\Models\Search;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SearchResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Search $search)
    {
        $this->authorize('view', $search->team);

        return ResultResource::collection($search->results()->with('episode', 'episode.show', 'epsiode.show.categories')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Search $search, Request $request)
    {
        $this->authorize('view', $search->team);

        $request->validate([
            'episode_id' => [
                'required',
                'exists:episodes,id'
            ],
            'sections' => [
                'required',
                'array'
            ],
            'sections.*.t' => [
                'required',
                'string'
            ],
            'sections.*.s' => [
                'required',
                'numeric',
                'min:0'
            ],
            'sections.*.e' => [
                'required',
                'numeric',
                'min:0'
            ]
        ]);

        $result = $search->results()->create([
            'episode_id' => $request->get('episode_id'),
            'sections' => $request->get('sections'),
            'query' => $search->query
        ]);

        return new ResultResource($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(Search $search, Result $result)
    {
        $this->authorize('view', $search->team);

        return new ResultResource($result);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Search $search, Result $result)
    {
        $this->authorize('destroy', $search->team);

        $result->delete();

        return response()->json([], 204);
    }
}
