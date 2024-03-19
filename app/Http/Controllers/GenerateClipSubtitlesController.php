<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateClipSubtitles;
use App\Models\Clip;
use Illuminate\Http\Request;

class GenerateClipSubtitlesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Clip $clip, Request $request)
    {
        $this->authorize('view', $clip->team);

        $clip->update([
            'subtitles' => null
        ]);

        GenerateClipSubtitles::dispatch($clip);

        return $clip->subtitles;
    }
}
