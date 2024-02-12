<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use PodcastIndex\Client;

class PodcastIndexService
{
    private $apiKey;

    private $apiSecret;

    protected $baseUrl = 'https://api.podcastindex.org/api/1.0/';

    protected $client;

    public function __construct()
    {
        $this->apiKey = config('services.podcast_index.key');  
        $this->apiSecret = config('services.podcast_index.secret');   

        $this->client = new Client([
            'app' => 'AppName',
            'key' => $this->apiKey,
            'secret' => $this->apiSecret
        ]);
    }

    public static function make()
    {
        return app(PodcastIndexService::class);
    }

    public function get($path, $query = [])
    {
        return $this->client->get($path, $query)->json();
    }

    public function searchByTitle($title)
    {
        return $this->client->get('/search/bytitle', [
            'q' => $title
        ])->json();
    }

}