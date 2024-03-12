<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Search;
use App\Models\Show;
use App\Models\Team;
use App\Services\PineconeService;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class PerformSemanticSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $request->validate([
            'query' => [
                'required',
                'string'
            ],
            'limit' => [
                'required',
                'numeric',
                'min:20',
                'max:20'
            ],
            'offset' => [
                'required',
                'numeric',
                'min:0'
            ],
            'include_categories' => [
                'nullable',
                'array'
            ],
            'include_categories.*' => [
                'exists:categories,id'
            ],
            'exclude_categories' => [
                'nullable',
                'array'
            ],
            'exclude_categories.*' => [
                'exists:categories,id'
            ],
            'include_shows' => [
                'nullable',
                'array'
            ],
            'include_shows.*' => [
                'exists:shows,id'
            ],
            'exclude_shows' => [
                'nullable',
                'array'
            ],
            'exclude_shows.*' => [
                'exists:shows,id'
            ]
        ]);

        $filter = [];

        if (count($request->get('include_shows')) || count($request->get('exclude_shows'))) {
            $filter["show_id"] = [];
        }

        if (count($request->get('include_categories')) || count($request->get('exclude_categories'))) {
            $filter["categories"] = [];
        }

        if (count($request->get('exclude_categories'))) {
            $filter["categories"]['$nin'] = collect($request->get('exclude_categories'))->map(function ($category) {
                return $category;
            })->values()->toArray();
        }
    
        if (count($request->get('include_categories'))) {
            $filter["categories"]['$in'] = collect($request->get('include_categories'))->map(function ($category) {
                return $category;
            })->values()->toArray();
        }

        if (count($request->get('exclude_shows'))) {
            $filter["show_id"]['$nin'] = collect($request->get('exclude_shows'))->map(function ($show) {
                return $show;
            })->values()->toArray();
        }
    
        if (count($request->get('include_shows'))) {
            $filter["show_id"]['$in'] = collect($request->get('include_shows'))->map(function ($show) {
                return $show;
            })->values()->toArray();
        }

        $embedding = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $request->get('query')
        ]);

        $matches = PineconeService::make()
            ->query($embedding['data'][0]['embedding'], $request->get('limit'), $filter);

        $showIds = $matches->pluck('metadata.show_id')->unique();

        $episodeIds = $matches->pluck('metadata.episode_id')->unique();

        $shows = Show::whereIn('id', $showIds)->get();

        $episodes = Episode::whereIn('id', $episodeIds)->get();

        $results = $matches->groupBy('metadata.episode_id');

                //         'id' => $order,
                // 'episode_id' => $hit['id'],
                // 'title' => $hit['title'],
                // 'published_at' => Carbon::createFromTimestamp($hit['published_at']),
                // 'categories' => $hit['categories'],
                // 'show' => $hit['show'],
                // 'description' => $hit['description'],
                // 'enclosure_url' => $hit['enclosure_url'],
                // 'sections' => $sections

        return [
            'data' => $results->values()->map(function ($result, $index) use ($shows, $episodes) {
                $episode = $episodes->where('id', $result[0]['metadata']['episode_id'])->first();
                $show = $shows->where('id', $result[0]['metadata']['show_id'])->first();

                return [
                    'id' => $index,
                    'episode_id' => $result[0]['metadata']['episode_id'],
                    'title' => $episode?->title,
                    'published_at' => $episode?->published_at,
                    'categories' => $result[0]['metadata']['categories'],
                    'show' => [
                        'id' => $show?->id,
                        'title' => $show?->title,
                        'image_url' => $show->image_url
                    ],
                    'description' => $episode?->description,
                    'enclosure_url' => $episode->enclosure_url,
                    'sections' => $result->map( function($hit) {
                        return [
                            'id' => $hit['id'],
                            's' => $hit['metadata']['start'],
                            'e' => $hit['metadata']['end'],
                            't' => $hit['metadata']['text'],
                            'score' => $hit['score']
                        ];
                    })->sortBy('score')
                ];
            }),
            // 'data' => $matches->map(function ($result) use ($shows, $episodes) {
            //     return [
            //         'id' => $result['id'],
            //         't' => $result['metadata']['text'],
            //         's' => $result['metadata']['start'],
            //         'e' => $result['metadata']['end'],
            //         'show' => $shows->where('id', $result['metadata']['show_id'])->first(),
            //         'episode' => $episodes->where('id', $result['metadata']['episode_id'])->first()
            //     ];
            // }),
            'show_ids' => $showIds,
            'episode_ids' => $episodeIds
        ];
    }
}
