<?php

namespace App\Services;

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

            $response = Http::timeout($this->timeout)
                ->retry(3, 1000)
                ->get($this->url . $endpoint, $query);

            if (!$response->successful()) {
                Log::error('WB API error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("WB API request failed: {$endpoint}, status {$response->status()}");
            }

            $json = $response->json();
            $items = $json['data'] ?? [];
            $lastPage = (int) ($json['meta']['last_page'] ?? 1);

            $onPage($items, $page, $lastPage);

            $page++;
        } while ($page <= $lastPage);
    }
}
