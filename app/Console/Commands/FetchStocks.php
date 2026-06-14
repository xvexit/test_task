<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\WbApiClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FetchStocks extends Command
{
    protected $signature = 'fetch:stocks {--dateFrom=} {--truncate}';
    protected $description = 'Fetch stocks from WB API and store into DB';

    public function handle(WbApiClient $client): int
    {
        if ($this->option('truncate')) {
            $this->warn('Truncating stocks table...');
            Stock::truncate();
        }

        $dateFrom = $this->option('dateFrom') ?: Carbon::now()->format('Y-m-d');

        $this->info("Fetching stocks from {$dateFrom}");

        $total = 0;
        $errors = 0;

        try {
            $client->fetchAll('/api/stocks', [
                'dateFrom' => $dateFrom,
            ], function (array $items, int $page, int $lastPage) use (&$total, &$errors) {
                try {
                    $rows = array_map(function ($item) {
                        return [
                            'date' => !empty($item['date']) ? $item['date'] : null,
                            'last_change_date' => !empty($item['last_change_date']) ? $item['last_change_date'] : null,
                            'supplier_article' => $item['supplier_article'] ?? null,
                            'tech_size' => $item['tech_size'] ?? null,
                            'barcode' => $item['barcode'] ?? null,
                            'quantity' => $item['quantity'] ?? null,
                            'is_supply' => $item['is_supply'] ?? null,
                            'is_realization' => $item['is_realization'] ?? null,
                            'quantity_full' => $item['quantity_full'] ?? null,
                            'warehouse_name' => $item['warehouse_name'] ?? null,
                            'in_way_to_client' => $item['in_way_to_client'] ?? null,
                            'in_way_from_client' => $item['in_way_from_client'] ?? null,
                            'nm_id' => $item['nm_id'] ?? null,
                            'subject' => $item['subject'] ?? null,
                            'category' => $item['category'] ?? null,
                            'brand' => $item['brand'] ?? null,
                            'sc_code' => $item['sc_code'] ?? null,
                            'price' => $item['price'] ?? null,
                            'discount' => $item['discount'] ?? null,
                        ];
                    }, $items);

                    Stock::insert($rows);

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

        $this->info("Done. Total saved: {$total} stocks, errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
