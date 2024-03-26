<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;
use Str;

class DownloadYoutubeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $url, public $path, public $filename)
    {
        //
    }

    public function downloadFromYoutubeUrl($url, $path, $filename)
    {
        $scriptPath = base_path('scripts/youtube-download.py');
        $command = escapeshellcmd("python3 $scriptPath " . $url . " " . Storage::disk('local')->path($path) . " " . $filename);

        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return $path . "/" . $filename;   
        }
        
        throw new Exception(implode(', ', $output));
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->downloadFromYoutubeUrl($this->url, $this->path, $this->filename);
    }
}
