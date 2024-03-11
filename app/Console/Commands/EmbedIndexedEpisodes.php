<?php

namespace App\Console\Commands;

use App\Jobs\UpsertSectionsToVectorDb;
use App\Models\Episode;
use Bus;
use Cache;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Throwable;

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
        $batchId = Cache::get('upsert_vector_batch_id');

        if ($batchId) {
            $batch = Bus::findBatch($batchId);

            if ($batch && $batch?->pendingJobs > 10 && ! $batch?->canceled()) return;
        }

        $episodes = Episode::where("status", "indexed")
            ->whereNull("embedded_at")
            ->join("shows", "episodes.show_id", "=", "shows.id")
            ->where("shows.active", 1)
            ->orderBy("shows.priority", "desc")
            ->orderBy("episodes.published_at", "desc")
            ->select("episodes.*")
            ->limit(100)
            ->get();

        $jobs = [];

        foreach ($episodes as $episode) {
            $jobs[] = new UpsertSectionsToVectorDb($episode);
        }

        $batch = Bus::batch($jobs)->progress(function (Batch $batch) {
            // A single job has completed successfully...
        })->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // 
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->name("Upsert epsiode segments to Vector DB")->onQueue('embedding')->dispatch();

        Cache::set('upsert_vector_batch_id', $batch->id, now()->addHour());
    }
}
