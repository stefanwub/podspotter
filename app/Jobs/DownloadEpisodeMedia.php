<?php

namespace App\Jobs;

use App\Models\Episode;
use Bus;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;
use Str;

class DownloadEpisodeMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Episode $episode)
    {
        //
    }

    public function downloadAndSaveFile($url, $fileName)
    {
        // Extract the filename and extension from the URL
        $path = parse_url($url, PHP_URL_PATH);
        $pathInfo = pathinfo($path);
        $fileExtension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
        $newFileName = $fileName . '.' . $fileExtension;

        // Download and save the file
        $fileContents = file_get_contents($url);
        if ($fileContents !== false) {
            Storage::disk('local')->put($newFileName, $fileContents);
            return $newFileName;
        } else {
            return null;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->episode->mediaFile) return;

        if ($this->episode->medium === 1) {
            $path = 'clips';

            $filename = Str::uuid() . '.mp4';

            $episode = $this->episode;

            DownloadYoutubeVideo::dispatchSync($this->episode->enclosure_url, $path, $filename);
            CopyFromLocalToStorage::dispatchSync($path, $filename);

            $episode->mediaFile()->create([
                'video_storage_key' => $path . '/' . $filename,
                'storage_disk' => config('filesystems.default')
            ]);

            return;
        }

        $waveformFileName = "waveforms/episodes/" . $this->episode->id . ".json";
            
        Storage::disk('local')->put($waveformFileName, "");

        $audioFileName = $this->downloadAndSaveFile($this->episode->enclosure_url, "media/episodes/" . $this->episode->id);

        $command = "audiowaveform -i " . Storage::disk('local')->path($audioFileName) . " -o" . Storage::disk('local')->path($waveformFileName) . " --pixels-per-second 10 -b 8 2>&1";
            
        $output = [];
        $result_code = 0;

        exec($command, $output, $result_code);

        Storage::put($waveformFileName, Storage::disk('local')->get($waveformFileName));
        Storage::put($audioFileName, Storage::disk('local')->get($audioFileName));

        $this->episode->mediaFile()->create([
            'audio_storage_key' => $audioFileName,
            'storage_disk' => config('filesystems.default'),
            'waveform_storage_key' =>  $waveformFileName
        ]);

        Storage::disk('local')->delete($waveformFileName);
        Storage::disk('local')->delete($waveformFileName);
    }
}
