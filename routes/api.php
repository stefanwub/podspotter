<?php

use App\Models\Episode;
use App\Models\Section;
use App\Services\LocalWhisperService;
use App\Services\PodcastIndexService;
use App\Services\ScrapeChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenAI\Laravel\Facades\OpenAI;
use Pgvector\Laravel\Distance;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('search', function(Request $request) {
    return Section::search($request->get('q'))
        ->usingWebSearchQuery()->get();
});

Route::get('semantic-search', function(Request $request) {
    $embedding = OpenAI::embeddings()->create([
        'model' => 'text-embedding-3-small',
        'input' => $request->get('q')
    ]);

    return Section::query()->nearestNeighbors('embedding', $embedding->embeddings[0]->embedding, Distance::L2)->take(5)->get();
});


Route::get('scrape', function(Request $request) {
    // https://chartable.com/charts/itunes/nl-all-podcasts-podcasts
    // https://chartable.com/charts/spotify/netherlands-top-podcasts

    return ScrapeChartService::make()->scrapePages('https://chartable.com/charts/itunes/nl-all-podcasts-podcasts');
});

Route::get('rss-feed', function(Request $request) {
    // https://chartable.com/charts/itunes/nl-all-podcasts-podcasts
    // https://chartable.com/charts/spotify/netherlands-top-podcasts

    return ScrapeChartService::make()->getRssFeed($request->query('q'));
});

Route::get('search-podcast', function (Request $request) {
    return PodcastIndexService::make()->searchByTitle($request->query('q'));
});

Route::get('podcast-index-categories', function () {
    return PodcastIndexService::make()->get('/categories/list');
    //return PodcastIndexService::make()->get('/podcasts/trending', ['lang' => 'nl,nl-nl', 'cat' => 'Culture', 'max' => 1000]);
});

Route::get('podcast-index', function () {
    return PodcastIndexService::make()->get('/podcasts/trending', ['lang' => 'nl,nl-nl', 'cat' => 'Fitness', 'max' => 1000]);
});

Route::get('/search', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->post(config('scout.meilisearch.host') . '/indexes/episodes/search', [
        'q' => $request->get('q'),
        'attributesToCrop' => ['sections'],
        'attributesToRetrieve' => ['_formatted', 'show', 'title', 'id', 'published_at', 'categories', 'description'],
        'attributesToHighlight' => ['sections', 'description'],
        // 'showMatchesPosition' => true,
        'limit' => $request->query('limit') ? intval($request->query('limit')) : 10,
        'cropLength' => 20
    ]);

    // $response = Http::withHeaders([
    //     'X-Meili-API-Key' => config('scout.meilisearch.key'),
    //     'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    // ])->delete(config('scout.meilisearch.host') . '/indexes/episodes');

    $hits = [];

    foreach ($response->json('hits') as $hit) {
        $hits[] = [
            // 'id' => $hit['id'],
            // 'title' => $hit['title'],
            // 'published_at' => isset($hit['published_at']) ? $hit['published_at'] : null,
            // 'categories' => $hit['categories'],
            // 'show' => $hit['show'],
            // 'description' => $hit['description'],
            ...$hit,
            '_formatted' => [
                'sections' => collect($hit['_formatted']['sections'])->filter(function ($s) {
                    return Str::contains($s['t'], '<em>');
                })->values()
            ]
        ];
    }

    return [
        ...$response->json(),
        'hits' => $hits
    ];
});

Route::get('/search/stats', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->get(config('scout.meilisearch.host') . '/stats');

    return $response->json();
});