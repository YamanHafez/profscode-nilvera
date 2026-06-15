<?php

namespace ProfsCode\Nilvera\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class NilveraClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $token = null;
    protected bool $debug = false;

    public function __construct()
    {
        $this->baseUrl = config('nilvera.base_url', 'https://api.nilvera.com');
        $this->apiKey = config('nilvera.api_key', '');
    }

    public function debug(bool $state = true): self
    {
        $this->debug = $state;
        return $this;
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

    // Hata durumunda Nilvera'nın döndürdüğü tam yanıt gövdesini exception mesajına ekler
    // (Laravel'in varsayılan RequestException mesajı yanıtı 500 karakterde kesiyor)
    protected function throwHandler()
    {
        return function ($response, $e) {
            throw new \RuntimeException(
                "Nilvera API error ({$response->status()}): " . $response->body(),
                0,
                $e
            );
        };
    }

    protected function asCurl(string $method, string $url, array $data = []): string
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => 'https://portal.nilvera.com',
            'Referer' => 'https://portal.nilvera.com/',
        ];

        $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');

        if ($method === 'GET' && !empty($data)) {
            $fullUrl .= (strpos($fullUrl, '?') === false ? '?' : '&') . http_build_query($data);
        }

        $curl = "curl -X {$method} '{$fullUrl}'";
        foreach ($headers as $key => $value) {
            $curl .= " -H '{$key}: {$value}'";
        }

        if ($method !== 'GET' && !empty($data)) {
            $curl .= " -d '" . json_encode($data) . "'";
        }

        return $curl;
    }

    public function get(string $url, array $query = [])
    {
        if ($this->debug) {
            return $this->asCurl('GET', $url, $query);
        }
        return $this->client()->get($url, $query)->throw($this->throwHandler())->json();
    }

    public function getRaw(string $url, array $query = [])
    {
        if ($this->debug) {
            return $this->asCurl('GET', $url, $query);
        }
        return $this->client()->get($url, $query)->throw($this->throwHandler())->body();
    }

    public function post(string $url, array $data = [])
    {
        if ($this->debug) {
            return $this->asCurl('POST', $url, $data);
        }
        return $this->client()->post($url, $data)->throw($this->throwHandler())->json();
    }

    public function postRaw(string $url, array $data = [])
    {
        if ($this->debug) {
            return $this->asCurl('POST', $url, $data);
        }
        return $this->client()->post($url, $data)->throw($this->throwHandler())->body();
    }

    public function delete(string $url, array $data = [])
    {
        if ($this->debug) {
            return $this->asCurl('DELETE', $url, $data);
        }
        return $this->client()->withBody(json_encode($data), 'application/json')->delete($url)->throw($this->throwHandler())->json();
    }

    public function put(string $url, array $data = [])
    {
        if ($this->debug) {
            return $this->asCurl('PUT', $url, $data);
        }
        return $this->client()->put($url, $data)->throw($this->throwHandler())->json();
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

        return $request->post($url)->throw($this->throwHandler())->json();
    }
}
