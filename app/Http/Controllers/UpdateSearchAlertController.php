<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchResource;
use App\Models\Search;
use Illuminate\Http\Request;

class UpdateSearchAlertController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Search $search, Request $request)
    {
        $this->authorize('update', $search->team);

        $request->validate([
            'alerts' => [
                'required',
                'boolean'
            ],
            'older_episode_alerts' => [
                'required',
                'boolean'
            ]
        ]);

        $search->update([
            'alerts' => $request->get('alerts'),
            'older_episode_alerts' => $request->get('older_episode_alerts'),
        ]);

        return new SearchResource($search);
    }
}
