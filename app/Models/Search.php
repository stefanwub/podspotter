<?php

namespace App\Models;

use App\Http\Resources\ResultResource;
use Carbon\Carbon;
use Http;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Str;

class Search extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'query',
        'alerts',
        'saved_at',
        'older_episode_alerts',
        'order_by'
    ];

    protected $casts = [
        'older_episode_alerts' => 'boolean',
        'saved_at' => 'datetime',
        'alerts' => 'boolean'
    ];

    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps()
            ->withPivot('include');
    }

    public function excludeCategories()
    {
        return $this->categories->where('pivot.include', false);
    }

    public function includeCategories()
    {
        return $this->categories->where('pivot.include', true);
    }

    public function shows() : BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'search_show')
            ->withTimestamps()
            ->withPivot('include');
    }

    public function excludeShows()
    {
        return $this->shows->where('pivot.include', false);
    }

    public function includeShows()
    {
        return $this->shows->where('pivot.include', true);
    }

    public function results() : HasMany
    {
        return $this->hasMany(Result::class)->orderBy('created_at', 'desc')->orderBy('order');
    }

    public function filteredResultsQuery()
    {
        $resultsQuery = $this->results();

        if ($this->categories->isNotEmpty() || $this->shows->isNotEmpty()) {
            $resultsQuery->whereHas('episode', function ($q) {
                $q->whereHas('show', function ($q) {
                    $q->whereHas('categories', function ($q) {
                        $q->whereIn('id', $this->includeCategories()->pluck('id'))
                            ->whereNotIn('id', $this->excludeCategories()->pluck('id'));
                    });
                })->whereIn('show_id', $this->includeShows())->whereNotIn('show_id', $this->excludeShows());
            });
        }

        return $resultsQuery;
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team() : BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function syncByRequest(Request $request)
    {
        $categories = [];
        $shows = [];

        if ($request->get('include_categories')) {
            foreach ($request->get('include_categories') as $category) {
                $categories[$category] = ['include' => true];
            }
        }

        if ($request->get('exclude_categories')) {
            foreach ($request->get('exclude_categories') as $category) {
                $categories[$category] = ['include' => false];
            }
        }

        if ($request->get('include_shows')) {
            foreach ($request->get('include_shows') as $show) {
                $shows[$show] = ['include' => true];
            }
        }

        if ($request->get('exclude_shows')) {
            foreach ($request->get('exclude_shows') as $show) {
                $shows[$show] = ['include' => true];
            }
        }

        $this->categories()->sync($categories);

        $this->shows()->sync($shows);
    }

    public function getResult($hit, $sections, $order)
    {
        $result = $this->results->where('episode_id', $hit['id'])->first();

        return [
            'id' => $order,
            'episode_id' => $hit['id'],
            'title' => $hit['title'],
            'published_at' => Carbon::createFromTimestamp($hit['published_at']),
            'categories' => $hit['categories'],
            'show' => $hit['show'],
            'description' => $hit['description'],
            'enclosure_url' => $hit['enclosure_url'],
            'saved_result_id' => $result?->id,
            'saved_result_at' => $result?->updated_at,
            'sections' => $sections
        ];
    }

    public function perform($offset = 0, $limit = 20)
    {
        $filter = [];

        if ($this->includeCategories()->isNotEmpty()) {
            $filter[] = $this->includeCategories()->map(function ($category) {
                return "categories = " . $category->id;
            })->values();
        }

        if ($this->excludeCategories()->isNotEmpty()) {
            $filter[] = $this->excludeCategories()->map(function ($category) {
                return "categories != " . $category->id;
            })->values();
        }

        if ($this->includeShows()->isNotEmpty()) {
            $filter[] = $this->includeShows()->map(function ($show) {
                return "show_id = " . $show->id;
            })->values();
        }

        if ($this->excludeShows()->isNotEmpty()) {
            $filter[] = $this->excludeShows()->map(function ($show) {
                return "show_id != " . $show->id;
            })->values();
        }

        return self::performSearch($this->query, $offset, $limit, $filter);
    }

    public static function performSearch($query, $offset = 0, $limit = 20, $filter = null)
    {
        $body = [
            'q' => '"' . $query . '"',
            'attributesToCrop' => ['sections', 'description'],
            'attributesToRetrieve' => ['_formatted', 'show', 'title', 'id', 'published_at', 'categories', 'description', 'enclosure_url'],
            'attributesToHighlight' => ['sections', 'description'],
            'attributesToSearchOn' => ['sections.t'],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
            'offset' => $offset,
            'limit' => $limit,
            'cropLength' => 100,
            'sort' => ['published_at:asc']
        ];

        if ($filter && count($filter)) {
            $body['filter'] = $filter;
        }

        $response = Http::withHeaders([
            'X-Meili-API-Key' => config('scout.meilisearch.key'),
            'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
        ])->post(config('scout.meilisearch.host') . '/indexes/episodes/search', $body);

        $results = [];

        $order = $offset;

        foreach ($response->json('hits') as $hit) {

            $sections = collect($hit['_formatted']['sections'])->filter(function ($s) {
                return Str::contains($s['t'], '<mark>');
            })->values();

            $result = [
                'id' => $order,
                'episode_id' => $hit['id'],
                'title' => $hit['title'],
                'published_at' => Carbon::createFromTimestamp($hit['published_at']),
                'categories' => $hit['categories'],
                'show' => $hit['show'],
                'description' => $hit['description'],
                'enclosure_url' => $hit['enclosure_url'],
                'sections' => $sections
            ];

            $results[] = $result;

            $order++;
        }

        return [
            'meta' => [
                'per_page' => $response->json('limit'),
                'total' => $response->json('estimatedTotalHits'),
                'current_page' => ($offset / $limit) + 1
            ],
            'data' => $results
        ];
    }
}
