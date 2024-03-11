<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GpuController;
use App\Http\Controllers\PerformSearchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchResultController;
use App\Http\Controllers\UpdateSearchAlertController;
use App\Http\Controllers\WhisperJobController;
use App\Http\Resources\UserResource;
use App\Models\Episode;
use App\Services\PodcastIndexService;
use Google\Cloud\Compute\V1\InstancesClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenAI\Laravel\Facades\OpenAI;
use Pgvector\Laravel\Distance;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Google\Cloud\Compute\V1\Instance;

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

Route::middleware('auth:sanctum')->get('/users/me', function (Request $request) {
    return new UserResource($request->user());
});

Route::apiResource('teams.searches', SearchController::class);
Route::apiResource('searches.results', SearchResultController::class)->except('update');
Route::put('/searches/{search}/update-alerts', UpdateSearchAlertController::class)->name('search.update-alerts');

Route::post('/teams/{team}/perform-search', PerformSearchController::class)->name('team.perform-search');

Route::apiResource('categories', CategoryController::class);

Route::apiResource('gpus', GpuController::class);
Route::apiResource('whisper-jobs', WhisperJobController::class)->only('index', 'show');

// Route::get('/search', function (Request $request) {
//     $response = Http::withHeaders([
//         'X-Meili-API-Key' => config('scout.meilisearch.key'),
//         'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
//     ])->post(config('scout.meilisearch.host') . '/indexes/episodes/search', [
//         'q' => $request->get('q'),
//         'attributesToCrop' => ['sections', 'description'],
//         'attributesToRetrieve' => ['_formatted', 'show', 'title', 'id', 'published_at', 'categories', 'description', 'enclosure_url'],
//         'attributesToHighlight' => ['sections', 'description'],
//         // 'showMatchesPosition' => true,
//         'attributesToSearchOn' => ['sections.t'],
//         'limit' => $request->query('limit') ? intval($request->query('limit')) : 10,
//         'cropLength' => 20
//     ]);

//     // $response = Http::withHeaders([
//     //     'X-Meili-API-Key' => config('scout.meilisearch.key'),
//     //     'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
//     // ])->delete(config('scout.meilisearch.host') . '/indexes/episodes');

//     $hits = [];

//     foreach ($response->json('hits') as $hit) {
//         $hits[] = [
//             // 'id' => $hit['id'],
//             // 'title' => $hit['title'],
//             // 'published_at' => isset($hit['published_at']) ? $hit['published_at'] : null,
//             // 'categories' => $hit['categories'],
//             // 'show' => $hit['show'],
//             // 'description' => $hit['description'],
//             ...$hit,
//             '_formatted' => [
//                 'sections' => collect($hit['_formatted']['sections'])->filter(function ($s) {
//                     return Str::contains($s['t'], '<em>');
//                 })->values()
//             ]
//         ];
//     }

//     return [
//         ...$response->json(),
//         'hits' => $hits
//     ];
// });

// Route::get('/search/shows', function (Request $request) {
//     $response = Http::withHeaders([
//         'X-Meili-API-Key' => config('scout.meilisearch.key'),
//         'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
//     ])->post(config('scout.meilisearch.host') . '/indexes/shows/search', [
//         'q' => $request->get('q'),
//         'attributesToCrop' => ['title', 'description'],
//         'attributesToRetrieve' => ['_formatted', 'title', 'id', 'published_at', 'categories', 'description', 'image_url'],
//         'attributesToHighlight' => ['title', 'description'],
//         // 'showMatchesPosition' => true,
//         'attributesToSearchOn' => ['title'],
//         'limit' => $request->query('limit') ? intval($request->query('limit')) : 10,
//         'cropLength' => 20
//     ]);

//     return $response->json();
// });

Route::get('/search/stats', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->get(config('scout.meilisearch.host') . '/stats');

    return $response->json();
});

Route::get('/search/settings', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->get(config('scout.meilisearch.host') . '/indexes/episodes/settings');

    return $response->json();
});

Route::get('/search/settings-update', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->patch(config('scout.meilisearch.host') . '/indexes/episodes/settings', [
        'sortableAttributes' => ['published_at', 'indexed_at']
    ]);

    return $response->json();
});

Route::get('/search/cancel-tasks', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->post(config('scout.meilisearch.host') . '/tasks/cancel?types=documentAdditionOrUpdate');

    return $response->json();
});

Route::get('/search/tasks', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->get(config('scout.meilisearch.host') . '/tasks?limit=100');

    return $response->json();
});

// Route::get('/search/delete-index', function (Request $request) {
//     $response = Http::withHeaders([
//         'X-Meili-API-Key' => config('scout.meilisearch.key'),
//         'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
//     ])->delete(config('scout.meilisearch.host') . '/indexes/shows');

//     return $response->json();
// });

Route::get('/search/enable-vector', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->patch(config('scout.meilisearch.host') . '/experimental-features', [
        'vectorStore' => true
    ]);

    return $response->json();
});

// Route::get('/search/test-vector', function (Request $request) {
//     $response = Http::withHeaders([
//         'X-Meili-API-Key' => config('scout.meilisearch.key'),
//         'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
//     ])->post(config('scout.meilisearch.host') . '/indexes/sections/documents', [
//         [
//             'id' => 1,
//             'show' => [
//                 'id' => 'test',
//                 'title' => 'Test'
//             ],
//             'show_id' => 'test',
//             'episode_id' => 'episode-id-test',
//             'start' => 0,
//             'end' => 10,
//             'text' => 'In meditatie ervaar je een staat van rust die nog dieper is dan slaap.'
//         ]
//     ]);

//     return $response->json();
// });

Route::get('/search/sections', function (Request $request) {
    $response = Http::withHeaders([
        'X-Meili-API-Key' => config('scout.meilisearch.key'),
        'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
    ])->post(config('scout.meilisearch.host') . '/indexes/sections/search', [
        'q' => $request->query('q') ?? ''
    ]);

    return $response->json();
});

// Route::get('/podcast-index', function (Request $request) {
//     return PodcastIndexService::make()->get('podcasts/trending', [
//         'lang' => 'nl',
//         'max' => 1000
//     ]);
// });

// Route::get('/podcast-index/podcasts/{feedId}', function ($feedId, Request $request) {
//     return PodcastIndexService::make()->get("podcasts/byfeedid", [
//         'id' => $feedId
//     ]);
// });

// Route::get('/podcast-index/search', function (Request $request) {
//     return PodcastIndexService::make()->get("search/bytitle", [
//         'q' => $request->query('q')
//     ]);
// });

// Route::get('/google-instances', function () {
//     $instances = new InstancesClient();
//     $array = [];
//     foreach ($instances->list('my-project-1496764198259', 'us-central1-a') as $instance) {
//         $ip = null;

//         foreach ($instance->getNetworkInterfaces() as $interface) {
//             foreach ($interface->getAccessConfigs() as $config) {
//                 $ip = $config->getNatIP();
//             }
//         }

//         $array[] = [
//             'name' => $instance->getName(),
//             'status' => $instance->getStatus(),
//             'ip' => $ip,
//             'zone' => $instance->getZone()
//         ];
//     }

//     return $array;

//     // $instance = $instances->get('instance-20240306-222216', 'my-project-1496764198259', 'us-central1-a');

//     // $interfaces = [];

//     // foreach ($instance->getNetworkInterfaces() as $interface) {
//     //     $interfaces[] = $interface->getAccessConfigs()[0]->getNatIP();
//     // }

//     // return [
//     //     'name' => $instance->getName(),
//     //     'status' => $instance->getStatus(),
//     //     'network' => $interfaces
//     // ];
// });


// Route::get('/create-google-instance', function () {
// $instancesClient = new InstancesClient();
// $instance = (new Instance())
//     ->setName('transcribe-gpu-1')
//     ->setSourceMachineImage('projects/my-project-1496764198259/global/machineImages/whisper-spot-image-t4-1gpu');

// $operationResponse = $instancesClient->insert($instance, 'my-project-1496764198259', 'us-central1-a');
// $operationResponse->pollUntilComplete();

// if ($operationResponse->operationSucceeded()) {
//     return 'Instance created successfully.';
// } else {
//     $error = $operationResponse->getError();
//     return 'Instance creation failed: ' . $error->getMessage();
// }
// });

// Route::get('/stop-google-instance', function () {
//     $instancesClient = new InstancesClient();
//     $instancesClient->stop('testname', 'my-project-1496764198259', 'us-central1-a');
// });

// Route::get('/delete-google-instance', function () {
//     $instancesClient = new InstancesClient();
//     $instancesClient->delete('testname', 'my-project-1496764198259', 'us-central1-a');
// });

// Route::get('embed-sections/{episode}', function (Episode $episode) {
//     $sections = $episode->getSectionsForEmbedding($episode->whisperJob)->slice(0, 15);

//     $embedding = OpenAI::embeddings()->create([
//         'model' => 'text-embedding-3-small',
//         'input' => $sections->map(function($section) {
//             return "Fragment uit " . $section['show']['title'];
//         })
//     ]);

//     return $embedding['data'][0]['embedding'];
// });