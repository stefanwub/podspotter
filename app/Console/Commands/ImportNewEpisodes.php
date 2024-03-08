<?php

namespace App\Console\Commands;

use App\Jobs\ImportShowEpisodes;
use App\Models\Show;
use Illuminate\Console\Command;

class ImportNewEpisodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-new-episodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check each show for new episodes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (Show::all() as $show) {
            ImportShowEpisodes::dispatch($show)->onQueue('import-episodes');
        }
    }
}
