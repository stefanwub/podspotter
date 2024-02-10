<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;
use willvincent\Feeds\Facades\FeedsFacade;

class Show extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function episodes() : HasMany
    {
        return $this->hasMany(Episode::class);
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

        $author = $feed->get_author();
        
        if (! in_array($feed->get_language(), ['nl', 'nl-nl'])) {
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
            'language' => $feed->get_language()
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
        $feed = FeedsFacade::make($this->feed_url);

        foreach ($feed->get_items() as $item) {
            if ($this->episodes->where('guid', $item->get_id())->first()) {
                break;
            }

            $seasonNumber = null;
            $episodeNumber = null;
            $imageUrl = null;
            $duration = null;

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
                'guid' => $item->get_id(),
                'title' => $item->get_title(),
                'description' => $item->get_description(),
                'episode' => $episodeNumber,
                'season' => $seasonNumber,
                'image_url' => $imageUrl,
                'duration' => $durationNumber,
                'enclosure_url' => $item->get_enclosure()->link,
                'published_at' => Carbon::parse($item->get_date())
            ]);
        }

    }
}
