<?php

namespace App\Console\Commands;

use App\Jobs\ImportPodcasts as JobsImportPodcasts;
use Illuminate\Console\Command;

class ImportPodcasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-podcasts';

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
        JobsImportPodcasts::dispatch();
    }
}
