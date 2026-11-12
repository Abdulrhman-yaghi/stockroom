<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data may only be seeded in local or testing environments.');
        }
        if (Product::exists() || Customer::exists() || Order::exists()) {
            throw new RuntimeException('Demo seeding requires an empty database. Use a fresh disposable database.');
        }
        $admin = User::factory()->create(['name' => 'Demo Administrator', 'email' => 'admin@stockroom.test', 'password' => 'StockroomDemo!2026']);
        $admin->forceFill(['role' => 'admin'])->save();
        $staff = User::factory()->create(['name' => 'Demo Staff', 'email' => 'staff@stockroom.test', 'password' => 'StockroomDemo!2026']);
        $names = [['Studio headphones', 'AUD-001', 8900, 80], ['Mechanical keyboard', 'KEY-002', 12000, 90], ['USB C hub', 'HUB-003', 4500, 60], ['Desk light', 'LGT-004', 6500, 70], ['Laptop stand', 'STD-005', 5500, 50], ['Monitor arm', 'ARM-006', 9900, 75], ['Wireless mouse', 'MSE-007', 3900, 85], ['Desk mat', 'MAT-008', 2400, 80]];
        $products = collect();
        foreach ($names as [$name,$sku,$price,$stock]) {
            $products->push(Product::create(['name' => $name, 'sku' => $sku, 'price_cents' => $price, 'stock' => $stock, 'reorder_level' => 10, 'active' => true]));
        }
        $customers = collect();
        foreach (['Atlas Studio', 'Cedar Creative', 'Northline Labs', 'Olive Works', 'Pixel Workshop', 'Summit Design'] as $i => $company) {
            $customers->push(Customer::create(['name' => 'Demo Buyer '.($i + 1), 'company' => $company, 'email' => 'buyer'.($i + 1).'@example.test']));
        }
        $clock = now();
        try {
            for ($i = 0; $i < 32; $i++) {
                Carbon::setTestNow($clock->copy()->subDays(14 - ($i % 14))->setTime(9 + ($i % 8), 15));
                $actor = $i % 3 === 0 ? $staff : $admin;
                $order = app(OrderService::class)->place($actor, $customers[$i % 6]->id, [['product_id' => $products[$i % 8]->id, 'quantity' => 1 + ($i % 4)], ['product_id' => $products[($i + 3) % 8]->id, 'quantity' => 1]]);
                if ($i % 9 === 0) {
                    app(OrderService::class)->cancel($actor, $order);
                }
            }
        } finally {
            Carbon::setTestNow();
        }
        foreach ([$products[2], $products[4]] as $product) {
            $product->refresh();
            app(InventoryService::class)->adjust($admin, $product, 4 - $product->stock, 'Demo inventory reconciliation');
        }
    }
}
