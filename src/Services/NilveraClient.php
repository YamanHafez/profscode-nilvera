<?php

namespace ProfsCode\Nilvera\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class NilveraClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('nilvera.base_url', 'https://api.nilvera.com');
        $this->apiKey = config('nilvera.api_key', '');
    }

    // Yetkilendirilmiş HTTP istemcisi
    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json, text/plain, */*',
                'Content-Type' => 'application/json',
                'Origin' => 'https://portal.nilvera.com',
                'Referer' => 'https://portal.nilvera.com/',
            ]);
    }

    public function get(string $url, array $query = [])
    {
        return $this->client()->get($url, $query)->json();
    }

    public function post(string $url, array $data = [])
    {
        return $this->client()->post($url, $data)->json();
    }

    public function delete(string $url, array $data = [])
    {
        return $this->client()->withBody(json_encode($data), 'application/json')->delete($url)->json();
    }

    public function put(string $url, array $data = [])
    {
        return $this->client()->put($url, $data)->json();
    }

    public function upload(string $url, string $filePath, string $fileName, array $data = [])
    {
        $request = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->attach('file', file_get_contents($filePath), $fileName);

        foreach ($data as $key => $value) {
            $request->attach($key, $value);
        }

        return $request->post($url)->json();
    }
}
