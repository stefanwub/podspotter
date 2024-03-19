<?php

namespace App\Models;

use App\Jobs\DownloadEpisodeMedia;
use App\Jobs\GenerateClipSubtitles;
use App\Jobs\RenderClip;
use Bus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Storage;

class Clip extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'subtitles' => 'json'
    ];

    protected $appends = [
        'duration',
        'url'
    ];

    protected static function booted()
    {
        self::saving(function (Clip $clip): void {
            if ($clip->start_region !== $clip->getOriginal('start_region') || $clip->end_region !== $clip->getOriginal('end_region')) {
                $clip->subtitles = null;
                $clip->status = 'processing';
            }
        });

        self::saved(function (Clip $clip): void {
            if ($clip->status === 'processing' && $clip->getOriginal('status') !== 'processing') {
                Bus::chain([
                    new DownloadEpisodeMedia($clip->episode),
                    new RenderClip($clip)
                ])->dispatch();
            }

            if ($clip->status === 'completed' && $clip->getOriginal('status') !== 'completed') {
                GenerateClipSubtitles::dispatch($clip);
            }
        });
    }

    public function team() : BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function episode() : BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function posts() : HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getDurationAttribute()
    {
        return $this->end_region - $this->start_region;
    }

    public function getUrlAttribute()
    {
        if (! $this->storage_key) return '';

        return Storage::disk($this->storage_disk)->url($this->storage_key);
    }

    function hexToAssColor($hexColor) {
        // Remove '#' if it's present
        $hex = ltrim($hexColor, '#');

        // Split the hex color into its RGB components
        $r = substr($hex, 0, 2);
        $g = substr($hex, 2, 2);
        $b = substr($hex, 4, 2);

        // Convert RGB to BGR and prepend the alpha channel for full opacity
        $assColor = '00' . $b . $g . $r;

        // Prefix with &H and convert to uppercase
        $assColor = '&H' . strtoupper($assColor);

        return $assColor;
    }

    public function createAssSubtitles($x= 540, $y = 540, $color = '#FFFFFF', $background = '#000000')
    {
        $color = $this->hexToAssColor($color);

        $background = $this->hexToAssColor($background);

        $assContent = "[Script Info]
        ScriptType: v4.00+
        Collisions: Normal
        PlayResX: $x
        PlayResY: $y
        WrapStyle: 1

        [V4+ Styles]
        Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
        Style: Default,Arial,28,$color,$color,$background,$background,0,0,0,0,100,100,0,0,1,4,0,2,20,20,40,1  ; Adjust Outline for thicker border

        [Events]
        Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";

        foreach ($this->subtitles as $entry) {
            // Convert timestamps to ASS format
            $start = gmdate("H:i:s", $entry['start']) . '.' . sprintf('%02d', floor(($entry['start'] - floor($entry['start'])) * 100));
            $end = gmdate("H:i:s", $entry['end']) . '.' . sprintf('%02d', floor(($entry['end'] - floor($entry['end'])) * 100));
            // Add dialogue line
            $assContent .= "Dialogue: 0,$start,$end,Default,,0000,0000,0000,," . trim($entry['text']) . "\n";
        }

        return $assContent;
    }

    public function createSrtSubtitles()
    {
        $srtContent = "";
        $counter = 1; // Subtitle counter

        foreach ($this->subtitles as $entry) {
            // Convert start and end times to SRT format
            $startTime = gmdate("H:i:s", $entry['start']) . ',' . sprintf('%03d', ($entry['start'] - floor($entry['start'])) * 1000);
            $endTime = gmdate("H:i:s", $entry['end']) . ',' . sprintf('%03d', ($entry['end'] - floor($entry['end'])) * 1000);

            // Append subtitle number
            $srtContent .= $counter . "\n";
            // Append time range
            $srtContent .= $startTime . " --> " . $endTime . "\n";
            // Append text
            $srtContent .= trim($entry['text']) . "\n\n";

            $counter++;
        }

        return $srtContent;
    }
}
