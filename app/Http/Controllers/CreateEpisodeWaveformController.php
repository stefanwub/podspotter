<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Storage;

class CreateEpisodeWaveformController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Episode $episode, Request $request)
    {
        $filename = "waveforms/episodes/$episode->id.json";

        if (Storage::exists($filename)) return response()->json([
            'url' => Storage::url($filename)
        ]);

        Storage::put($filename, json_encode($request->all()));

        return response()->json([
            'url' => Storage::url($filename)
        ]);
    }

}
