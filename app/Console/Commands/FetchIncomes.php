<?php

namespace App\Console\Commands;

use App\Models\Income;
use App\Services\WbApiClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FetchIncomes extends Command
{
    protected $signature = 'fetch:incomes {--dateFrom=} {--dateTo=} {--truncate}';
    protected $description = 'Fetch incomes from WB API and store into DB';

    public function handle(WbApiClient $client): int
    {
        if ($this->option('truncate')) {
            $this->warn('Truncating incomes table...');
            Income::truncate();
        }

        $dateFrom = $this->option('dateFrom') ?: '2025-01-01';
        $dateTo = $this->option('dateTo') ?: Carbon::now()->format('Y-m-d');

        $this->info("Fetching incomes from {$dateFrom} to {$dateTo}");

        $total = 0;
        $errors = 0;

        try {
            $client->fetchAll('/api/incomes', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ], function (array $items, int $page, int $lastPage) use (&$total, &$errors) {
                try {
                    $rows = array_map(function ($item) {
                        return [
                            'income_id' => $item['income_id'] ?? null,
                            'number' => $item['number'] ?? null,
                            'date' => !empty($item['date']) ? $item['date'] : null,
                            'last_change_date' => !empty($item['last_change_date']) ? $item['last_change_date'] : null,
                            'supplier_article' => $item['supplier_article'] ?? null,
                            'tech_size' => $item['tech_size'] ?? null,
                            'barcode' => $item['barcode'] ?? null,
                            'quantity' => $item['quantity'] ?? null,
                            'total_price' => $item['total_price'] ?? null,
                            'date_close' => !empty($item['date_close']) ? $item['date_close'] : null,
                            'warehouse_name' => $item['warehouse_name'] ?? null,
                            'nm_id' => $item['nm_id'] ?? null,
                        ];
                    }, $items);

                    Income::insert($rows);

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

        $this->info("Done. Total saved: {$total} incomes, errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
