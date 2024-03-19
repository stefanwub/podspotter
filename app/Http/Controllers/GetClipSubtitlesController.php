<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use Illuminate\Http\Request;

class GetClipSubtitlesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Clip $clip, Request $request)
    {
        $this->authorize('view', $clip->team);

        return $clip->subtitles;
    }
}
