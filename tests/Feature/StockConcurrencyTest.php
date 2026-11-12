<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StockConcurrencyTest extends TestCase
{
    public function test_two_processes_cannot_buy_the_same_last_unit(): void
    {
        $path = storage_path('framework/testing/race-'.bin2hex(random_bytes(8)).'.sqlite');
        touch($path);
        config(['database.connections.race' => array_merge(config('database.connections.sqlite'), ['database' => $path]), 'database.default' => 'race']);
        Artisan::call('migrate', ['--database' => 'race', '--force' => true]);
        User::factory()->create();
        Customer::factory()->create();
        Product::factory()->create(['stock' => 1]);
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $path, 'DB_URL' => '', 'QUEUE_CONNECTION' => 'sync'];
        $first = new Process([PHP_BINARY, base_path('tests/Fixtures/place_order.php')], base_path(), $environment);
        $second = new Process([PHP_BINARY, base_path('tests/Fixtures/place_order.php')], base_path(), $environment);
        $first->start();
        $second->start();
        $first->wait();
        $second->wait();
        $codes = [$first->getExitCode(), $second->getExitCode()];
        sort($codes);
        $this->assertSame([0, 2], $codes, $first->getErrorOutput().$second->getErrorOutput());
        $this->assertSame(0, Product::first()->stock);
        $this->assertSame(1, Order::count());
        DB::purge('race');
    }
}
