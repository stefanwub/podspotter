<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;

class DownloadYoutubeVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-youtube-video {url} {output_path} {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scriptPath = base_path('scripts/youtube-download.py');
        $command = escapeshellcmd("python3 $scriptPath " . $this->argument('url') . " " . Storage::disk('local')->path($this->argument('output_path')) . " " . $this->argument('filename'));

        exec($command, $output, $return_var);

        if ($return_var === 0) {
            // Storage::put($this->argument('output_path') . '/' . $this->argument('filename'), Storage::disk('local')->get($this->argument('output_path') . '/' . $this->argument('filename')));

            $this->info('File saved to local disk at ' . Storage::disk('local')->path($this->argument('output_path') . '/' . $this->argument('filename')));   
        } else {
            $this->error(implode(", ", $output));
        }
    }
}
