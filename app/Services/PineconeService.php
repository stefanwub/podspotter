<?php

namespace App\Services;

use Http;

class PineconeService
{
    protected $apiKey;

    protected $host;

    public function __construct()
    {
        $this->apiKey = config('services.pinecone.api_key');

        $this->host = config('services.pinecone.host');
    }

    public static function make()
    {
        return new self;
    }

    protected function withHeaders()
    {
        return Http::withHeaders([
            'Api-Key' => $this->apiKey
        ]);
    }

    public function get($path, $query = [])
    {
        return $this->withHeaders()->get($this->host . '/' . $path, $query);
    }

    public function post($path, $data = [])
    {
        return $this->withHeaders()->post($this->host . '/' . $path, $data);
    }

    public function upsert($records = [], $namespace = 'spotter')
    {
        return $this->post('vectors/upsert', [
            "vectors" => $records,
            "namespace" => $namespace
        ]);
    }

    public function query($vectors, $topK = 10)
    {
        return $this->post('query', [
            'vectors' => $vectors,
            'topK' => $topK,
            "includeValues" => true
        ]);
    }

    public function deleteAll($namespace = 'spotter')
    {
        return $this->post('vectors/delete', [
            "deleteAll" => true,
            "namespace" => $namespace
        ]);
    }
}