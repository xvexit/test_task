<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\WbApiClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FetchOrders extends Command
{
    protected $signature = 'fetch:orders {--dateFrom=} {--dateTo=} {--truncate}';
    protected $description = 'Fetch orders from WB API and store into DB';

    public function handle(WbApiClient $client): int
    {
        if ($this->option('truncate')) {
            $this->warn('Truncating orders table...');
            Order::truncate();
        }

        $dateFrom = $this->option('dateFrom') ?: '2025-01-01';
        $dateTo = $this->option('dateTo') ?: Carbon::now()->format('Y-m-d');

        $this->info("Fetching orders from {$dateFrom} to {$dateTo}");

        $total = 0;
        $errors = 0;

        try {
            $client->fetchAll('/api/orders', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ], function (array $items, int $page, int $lastPage) use (&$total, &$errors) {
                try {
                    $rows = array_map(function ($item) {
                        return [
                            'g_number' => $item['g_number'] ?? null,
                            'date' => !empty($item['date']) ? $item['date'] : null,
                            'last_change_date' => !empty($item['last_change_date']) ? $item['last_change_date'] : null,
                            'supplier_article' => $item['supplier_article'] ?? null,
                            'tech_size' => $item['tech_size'] ?? null,
                            'barcode' => $item['barcode'] ?? null,
                            'total_price' => $item['total_price'] ?? null,
                            'discount_percent' => $item['discount_percent'] ?? null,
                            'warehouse_name' => $item['warehouse_name'] ?? null,
                            'oblast' => $item['oblast'] ?? null,
                            'income_id' => $item['income_id'] ?? null,
                            'odid' => isset($item['odid']) ? (string) $item['odid'] : null,
                            'nm_id' => $item['nm_id'] ?? null,
                            'subject' => $item['subject'] ?? null,
                            'category' => $item['category'] ?? null,
                            'brand' => $item['brand'] ?? null,
                            'is_cancel' => $item['is_cancel'] ?? null,
                            'cancel_dt' => !empty($item['cancel_dt']) ? $item['cancel_dt'] : null,
                        ];
                    }, $items);

                    Order::insert($rows);

                    $count = count($rows);
                    $total += $count;
                    $this->info("Page {$page}/{$lastPage}, saved {$count} rows");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Page {$page}/{$lastPage} failed: {$e->getMessage()}");
                }
            });
        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
        }

        $this->info("Done. Total saved: {$total} orders, errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
