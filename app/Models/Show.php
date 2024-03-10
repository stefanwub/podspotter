<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Str;
use willvincent\Feeds\Facades\FeedsFacade;

class Show extends Model
{
    use HasFactory, HasUuids, Searchable;

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'medium' => $this->medium,
            'ranking' => $this->ranking ?? 1000,
            'categories' => $this->categories->pluck('id')
        ];
    }

    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function episodes() : HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function searches() : BelongsToMany
    {
        return $this->belongsToMany(Search::class)
            ->withTimestamps()
            ->withPivot('include');
    }

    public function get_categories()
    {
        $feed = FeedsFacade::make($this->feed_url);

        $categories = $feed->get_channel_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'category');

        return $categories[0]['attribs']['']['text'];
    }

    public function import()
    {
        if (! $this->feed_url) return;

        $feed = FeedsFacade::make($this->feed_url);

        if (Str::contains($this->feed_url, 'youtube.com/feeds/videos.xml')) {
            $this->update([
                'title' => htmlspecialchars_decode($feed->get_title()),
                'language' => 'nl',
                'medium' => 'youtube'
            ]);

            return;
        }

        $author = $feed->get_author();
        
        if (! $this->language && ! in_array($feed->get_language(), ['nl', 'nl-nl'])) {
            $this->delete();

            return;
        }

        $categories = $feed->get_channel_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'category');

        $cats = [];

        foreach ($categories as $category) {
            $c = Category::where('name', $category['attribs']['']['text'])->first();

            if (! $c) {
                $c = Category::create([
                    'name' => $category['attribs']['']['text']
                ]);
            }

            $cats[] = $c->id;
        }

        $this->categories()->sync($cats);

        $this->update([
            'title' => htmlspecialchars_decode($feed->get_title()),
            'description' => strip_tags($feed->get_description()),
            'image_url' => $feed->get_image_url(),
            'author' => htmlspecialchars_decode($author?->get_name()),
            'language' => $this->language ?? $feed->get_language()
        ]);
    }

    protected function toSeconds($time)
    {
        // Check if the input is already a number (only seconds)
        if (is_numeric($time)) {
            return (int)$time;
        }

        // Split the time string by colon
        $parts = explode(':', $time);

        // Determine the format based on the number of parts and calculate seconds accordingly
        switch (count($parts)) {
            case 2: // Format is MM:SS
                list($minutes, $seconds) = $parts;
                return (int)$minutes * 60 + (int)$seconds;
            case 3: // Format is HH:MM:SS
                list($hours, $minutes, $seconds) = $parts;
                return (int)$hours * 3600 + (int)$minutes * 60 + (int)$seconds;
            default:
                // Invalid format, you could return false, 0, or trigger an error
                return 0;
        }

        return 0;
    }

    public function importEpisodesFromFeed()
    {
        if ($this->medium === 'youtube') {
            $this->importFromYoutubeFeed();
            return;
        }

        $feed = FeedsFacade::make($this->feed_url);

        foreach ($feed->get_items() as $item) {
            if ($this->episodes->where('guid', $item->get_id())->first()) {
                break;
            }

            $seasonNumber = null;
            $episodeNumber = null;
            $imageUrl = null;
            $duration = null;
            $durationNumber = 0;

            $season = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'season');
            $episode = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'episode');
            $image = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'image');
            $duration = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'duration');

            if ($season) {
                $seasonNumber = $season[0]['data'];
            }

            if ($episode) {
                $episodeNumber = $episode[0]['data'];
            }

            if ($image) {
                $imageUrl = $image[0]['attribs']['']['href'];
            }

            if ($duration) {
                $durationNumber = $this->toSeconds($duration[0]['data']);
            }

            $this->episodes()->create([
                'guid' => Str::limit($item->get_id(), 250),
                'title' => Str::limit($item->get_title(), 250),
                'description' => $item->get_description(),
                'episode' => $episodeNumber,
                'season' => $seasonNumber,
                'medium' => 0,
                'image_url' => Str::limit($imageUrl, 250),
                'duration' => $durationNumber,
                'enclosure_url' => $item->get_enclosure()->link,
                'published_at' => Carbon::parse($item->get_date())
            ]);
        }
    }

    public function importFromYoutubeFeed()
    {
        $feed = FeedsFacade::make($this->feed_url);

        $mrss_namespace = 'http://search.yahoo.com/mrss/';

        foreach ($feed->get_items() as $item) {
            if ($this->episodes->where('guid', $item->get_id())->first()) {
                break;
            }

            $media_group = $item->get_item_tags($mrss_namespace, 'group');
            $media_description = $media_group[0]['child'][$mrss_namespace]['description'][0]['data'];
            $media_thumbnail = $media_group[0]['child'][$mrss_namespace]['thumbnail'][0]['attribs']['']['url'];

            $this->episodes()->create([
                'guid' => Str::limit($item->get_id(), 250),
                'title' => Str::limit($item->get_title(), 250),
                'description' => $media_description,
                'medium' => 1,
                'image_url' => $media_thumbnail,
                'enclosure_url' => $item->get_enclosure()->link,
                'published_at' => Carbon::parse($item->get_date())
            ]);
        }
    }
}
