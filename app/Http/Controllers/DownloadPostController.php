<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Redirect;
use Storage;

class DownloadPostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Post $post, Request $request)
    {
        $url = Storage::disk($post->storage_disk)->temporaryUrl(
            $post->storage_key,
            now()->addWeek(),
            [
                'ResponseContentDisposition' => 'attachment; filename=' . $post->storage_key,
            ]
        );

        return Redirect::to($url);
    }
}
