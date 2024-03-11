<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\PineconeService;
use Exception;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;

class UpsertSectionsToVectorDb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Episode $episode)
    {
        //
    }

    protected function getVectors($sections)
    {
        $embedding = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => collect($sections)->map(function($section) {
                return "Fragment uit " . $section['show']['title'] . ": " . $section['text'];
            })
        ]);

        return [
            'meilisearch' => collect($sections)->map(function ($section, $index) use ($embedding) {
                return [
                    ...$section,
                    "_vectors" => [
                        "default" => $embedding['data'][$index]['embedding']
                    ]
                ];
            })->all(),
            'pinecone' => collect($sections)->map(function ($section, $index) use ($embedding) {
                return [
                    'id' => $section['id'],
                    'metadata' => [
                        'categories' => $section['categories'],
                        'title' => $section['title'],
                        'show_id' => $section['show_id'],
                        'episode_id' => $section['episode_id'],
                        'medium' => $section['medium'],
                        'published_at' => $section['published_at'],
                        'indexed_at' => $section['indexed_at'],
                        'text' => $section['text'],
                        'start' => $section['start'],
                        'end' => $section['end']
                    ],
                    "values" => $embedding['data'][$index]['embedding']
                ];
            })->all(),
        ];

        // return collect($sections)->map(function ($section, $index) use ($embedding) {
        //     return [
        //         ...$section,
        //         "_vectors" => [
        //             "default" => $embedding['data'][$index]['embedding']
        //         ]
        //     ];
        // })->all();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // if ($this->episode->embedded_at) return;

        $whisperJob = $this->episode->whisperJobs->whereIn('status', ['completed', 'succeeded'])->first();

        $count = 0;

        $sections = [];
        $sectionsWithVectors = [];
        $pineconeSections = [];

        foreach ($this->episode->getSectionsForEmbedding($whisperJob) as $section) {
            $count++;
            $sections[] = $section;

            if ($count > 15) {

                $sectionsWithVectors = array_merge($sectionsWithVectors, $this->getVectors($sections)['meilisearch']);
                $pineconeSections = array_merge($pineconeSections, $this->getVectors($sections)['pinecone']);

                sleep(1);

                $sections = [];
                $count = 0;
            }
        }

        if (count($sections)) {
            $sectionsWithVectors = array_merge($sectionsWithVectors, $this->getVectors($sections)['meilisearch']);
            $pineconeSections = array_merge($pineconeSections, $this->getVectors($sections)['pinecone']);
        }

        $response = Http::withHeaders([
            'X-Meili-API-Key' => config('scout.meilisearch.key'),
            'Authorization' => 'Bearer ' . config('scout.meilisearch.key'),
        ])->post(config('scout.meilisearch.host') . '/indexes/sections/documents?primaryKey=id', $sectionsWithVectors);

        if ($response->failed()) {
            throw new Exception($response->body());
        }

        $pineconeChunks = array_chunk($pineconeSections, 100);

        foreach ($pineconeChunks as $chunk) {
            $response = PineconeService::make()->upsert($chunk);

            if ($response->failed()) {
                throw new Exception($response->body());
            }
        }

        $this->episode->update([
            'embedded_at' => now()
        ]);
    }
}
