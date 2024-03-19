<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use Http;
use Illuminate\Http\Request;

class ClipSubtitlesWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Clip $clip, Request $request)
    {
        // if ($clip->subtitles) return response('', 200);

        $response = Http::withHeader('Authorization', 'Token ' . config('services.replicate.api_token'))
            ->get('https://api.replicate.com/v1/predictions/' . $request->get('id'));

        if ($response->successful() && $response->json('output.chunks')) {
            $clip->update([
                'subtitles' => collect($response->json('output.chunks'))->map(function ($chunk) {
                    return [
                        'start' => $chunk['timestamp'][0],
                        'end' => $chunk['timestamp'][1],
                        'text' => $chunk['text']
                    ];
                })->toArray()
            ]);

            return response('', 200);
        }

        return response('', 400);
    }
}
