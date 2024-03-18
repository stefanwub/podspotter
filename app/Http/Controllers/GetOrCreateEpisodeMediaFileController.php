<?php

namespace App\Http\Controllers;

use App\Jobs\DownloadEpisodeMedia;
use App\Models\Episode;
use Illuminate\Http\Request;
use Storage;

class GetOrCreateEpisodeMediaFileController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Episode $episode, Request $request)
    {
        $mediaFile = $episode->mediaFile;

        if (! $mediaFile) {
            DownloadEpisodeMedia::dispatchSync($episode);
        }

        $episode->refresh();

        return $episode->mediaFile;
    }
}
