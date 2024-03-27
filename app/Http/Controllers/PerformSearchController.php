<?php

namespace App\Http\Controllers;

use App\Models\Search;
use App\Models\Team;
use Illuminate\Http\Request;

class PerformSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $request->validate([
            'query' => [
                'nullable',
                'string'
            ],
            'limit' => [
                'required',
                'numeric',
                'min:20',
                'max:20'
            ],
            'offset' => [
                'required',
                'numeric',
                'min:0'
            ],
            'medium' => [
                'nullable',
                'in:0,1'
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

        $filter = [];

        if (count($request->get('exclude_categories'))) {
            $filter[] = collect($request->get('exclude_categories'))->map(function ($category) {
                return "categories != " . $category;
            })->values()->toArray();
        }
    
        if (count($request->get('include_categories'))) {
            $filter[] = collect($request->get('include_categories'))->map(function ($category) {
                return "categories = " . $category;
            })->values()->toArray();
        }

        if (count($request->get('exclude_shows'))) {
            $filter[] = collect($request->get('exclude_shows'))->map(function ($category) {
                return "show_id != " . $category;
            })->values()->toArray();
        }
    
        if (count($request->get('include_shows'))) {
            $filter[] = collect($request->get('include_shows'))->map(function ($category) {
                return "show_id = " . $category;
            })->values()->toArray();
        }

        if ($request->get('medium') || $request->get('medium') === '0') {
            $filter[] = [
                'medium = ' . $request->get('medium')
            ];
        }

        return Search::performSearch($request->get('query'), offset: $request->get('offset'), limit: $request->get('limit'), filter: $filter, includeClips: true);
    }
}
