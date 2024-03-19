<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use Illuminate\Http\Request;

class UpdateClipSubtitlesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Clip $clip, Request $request)
    {
        $this->authorize('view', $clip->team);

        $request->validate([
            'subtitles' => [
                'required',
                'array'
            ],
            'subtitles.*.start' => [
                'required',
                'numeric',
                'min:0'
            ],
            'subtitles.*.end' => [
                'required',
                'numeric',
                'min:0'
            ],
            'subtitles.*.text' => [
                'required'
            ]
        ]);

        $clip->update([
            'subtitles' => $request->get('subtitles')
        ]);

        return $clip->subtitles;
    }
}
