<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchAll extends Command
{
    protected $signature = 'fetch:all {--dateFrom=} {--dateTo=} {--truncate}';
    protected $description = 'Fetch all data types from WB API';

    public function handle(): int
    {
        $dateFrom = $this->option('dateFrom');
        $dateTo = $this->option('dateTo');
        $truncate = $this->option('truncate');

        $results = [];

        $commands = [
            'orders' => function () use ($dateFrom, $dateTo, $truncate) {
                $params = [];
                if ($dateFrom) $params['--dateFrom'] = $dateFrom;
                if ($dateTo) $params['--dateTo'] = $dateTo;
                if ($truncate) $params['--truncate'] = true;
                return $this->call('fetch:orders', $params);
            },
            'sales' => function () use ($dateFrom, $dateTo, $truncate) {
                $params = [];
                if ($dateFrom) $params['--dateFrom'] = $dateFrom;
                if ($dateTo) $params['--dateTo'] = $dateTo;
                if ($truncate) $params['--truncate'] = true;
                return $this->call('fetch:sales', $params);
            },
            'incomes' => function () use ($dateFrom, $dateTo, $truncate) {
                $params = [];
                if ($dateFrom) $params['--dateFrom'] = $dateFrom;
                if ($dateTo) $params['--dateTo'] = $dateTo;
                if ($truncate) $params['--truncate'] = true;
                return $this->call('fetch:incomes', $params);
            },
            'stocks' => function () use ($dateFrom, $truncate) {
                $params = [];
                if ($dateFrom) $params['--dateFrom'] = $dateFrom;
                if ($truncate) $params['--truncate'] = true;
                return $this->call('fetch:stocks', $params);
            },
        ];

        foreach ($commands as $name => $callback) {
            try {
                $exitCode = $callback();
                $results[$name] = $exitCode === 0;
            } catch (\Exception $e) {
                Log::error("fetch:{$name} failed with exception", [
                    'message' => $e->getMessage(),
                ]);
                $this->error("fetch:{$name} threw an exception: {$e->getMessage()}");
                $results[$name] = false;
            }
        }

        $status = array_map(fn ($ok) => $ok ? 'OK' : 'FAIL', $results);
        $this->info("Done. Orders: {$status['orders']}, Sales: {$status['sales']}, Incomes: {$status['incomes']}, Stocks: {$status['stocks']}");

        return in_array(false, $results, true) ? 1 : 0;
    }
}
