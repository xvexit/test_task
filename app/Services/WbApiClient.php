<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WbApiClient
{
    private string $url;
    private string $key;
    private int $limit;
    private int $timeout;

    public function __construct()
    {
        $this->url = rtrim(config('wbapi.url'), '/');
        $this->key = config('wbapi.key');
        $this->limit = (int) config('wbapi.limit', 500);
        $this->timeout = (int) config('wbapi.timeout', 60);
    }

    public function fetchAll(string $endpoint, array $params, callable $onPage): void
    {
        $page = 1;
        $lastPage = 1;

        do {
            $query = array_merge($params, [
                'page' => $page,
                'limit' => $this->limit,
                'key' => $this->key,
            ]);

            $response = $this->requestWithRetry($endpoint, $query);

            $json = $response->json();
            $items = $json['data'] ?? [];
            $lastPage = (int) ($json['meta']['last_page'] ?? 1);

            $onPage($items, $page, $lastPage);

            $page++;
            usleep(500000);
        } while ($page <= $lastPage);
    }

    private function requestWithRetry(string $endpoint, array $query): Response
    {
        $url = $this->url . $endpoint;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->get($url, $query);

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 429) {
                Log::warning("WB API 429, retrying in 5s (attempt {$attempt}/5)", [
                    'endpoint' => $endpoint,
                ]);
                sleep(5);
                continue;
            }

            if ($response->serverError()) {
                for ($retry = 1; $retry <= 3; $retry++) {
                    Log::warning("WB API {$response->status()}, retrying in 2s (attempt {$retry}/3)", [
                        'endpoint' => $endpoint,
                    ]);
                    sleep(2);

                    $response = Http::acceptJson()
                        ->timeout($this->timeout)
                        ->get($url, $query);

                    if ($response->successful()) {
                        return $response;
                    }

                    if ($response->status() === 429) {
                        continue 2;
                    }
                }
            }

            Log::error('WB API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("WB API request failed: {$endpoint}, status {$response->status()}");
        }

        throw new \RuntimeException("WB API rate limit exhausted: {$endpoint}");
    }
}
