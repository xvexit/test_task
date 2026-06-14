<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\WbApiClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FetchSales extends Command
{
    protected $signature = 'fetch:sales {--dateFrom=} {--dateTo=} {--truncate}';
    protected $description = 'Fetch sales from WB API and store into DB';

    public function handle(WbApiClient $client): int
    {
        if ($this->option('truncate')) {
            $this->warn('Truncating sales table...');
            Sale::truncate();
        }

        $dateFrom = $this->option('dateFrom') ?: '2025-01-01';
        $dateTo = $this->option('dateTo') ?: Carbon::now()->format('Y-m-d');

        $this->info("Fetching sales from {$dateFrom} to {$dateTo}");

        $total = 0;
        $errors = 0;

        try {
            $client->fetchAll('/api/sales', [
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
                            'is_supply' => $item['is_supply'] ?? null,
                            'is_realization' => $item['is_realization'] ?? null,
                            'promo_code_discount' => $item['promo_code_discount'] ?? null,
                            'warehouse_name' => $item['warehouse_name'] ?? null,
                            'country_name' => $item['country_name'] ?? null,
                            'oblast_okrug_name' => $item['oblast_okrug_name'] ?? null,
                            'region_name' => $item['region_name'] ?? null,
                            'income_id' => $item['income_id'] ?? null,
                            'sale_id' => $item['sale_id'] ?? null,
                            'odid' => isset($item['odid']) ? (string) $item['odid'] : null,
                            'spp' => $item['spp'] ?? null,
                            'for_pay' => $item['for_pay'] ?? null,
                            'finished_price' => $item['finished_price'] ?? null,
                            'price_with_disc' => $item['price_with_disc'] ?? null,
                            'nm_id' => $item['nm_id'] ?? null,
                            'subject' => $item['subject'] ?? null,
                            'category' => $item['category'] ?? null,
                            'brand' => $item['brand'] ?? null,
                            'is_storno' => $item['is_storno'] ?? null,
                        ];
                    }, $items);

                    Sale::insert($rows);

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

        $this->info("Done. Total saved: {$total} sales, errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
