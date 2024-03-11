<?php

namespace App\Console\Commands;

use App\Jobs\UpsertSectionsToVectorDb;
use App\Models\Episode;
use Illuminate\Console\Command;

class EmbedIndexedEpisodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:embed-indexed-episodes';

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
        $episodes = Episode::where("status", "indexed")
            ->whereNull("embedded_at")
            ->join("shows", "episodes.show_id", "=", "shows.id")
            ->where("shows.active", 1)
            ->orderBy("shows.priority", "desc")
            ->orderBy("episodes.published_at", "desc")
            ->select("episodes.*")
            ->limit(10)
            ->get();

        foreach ($episodes as $episode) {
            UpsertSectionsToVectorDb::dispatch($episode)->onQueue('embedding');
        }
    }
}