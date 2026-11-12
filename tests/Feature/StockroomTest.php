<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_uses_database_prices_and_decrements_stock(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'price_cents' => 1250]);
        $order = app(OrderService::class)->place($user, $customer->id, [['product_id' => $product->id, 'quantity' => 2]]);
        $this->assertSame(2500, $order->total_cents);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('activities', ['event' => 'order.confirmed']);
    }

    public function test_failed_order_rolls_back_all_stock_changes(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $first = Product::factory()->create(['stock' => 5]);
        $last = Product::factory()->create(['stock' => 0]);
        try {
            app(OrderService::class)->place($user, $customer->id, [['product_id' => $first->id, 'quantity' => 2], ['product_id' => $last->id, 'quantity' => 1]]);
            $this->fail('Expected validation failure');
        } catch (ValidationException $e) {
            $this->assertSame(5, $first->fresh()->stock);
            $this->assertSame(0, Order::count());
            $this->assertSame(0, Activity::count());
        }
    }

    public function test_cancellation_restores_stock_exactly_once(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $service = app(OrderService::class);
        $order = $service->place($user, $customer->id, [['product_id' => $product->id, 'quantity' => 2]]);
        $service->cancel($user, $order);
        $service->cancel($user, $order);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(1, Activity::where('event', 'order.cancelled')->count());
    }

    public function test_staff_cannot_adjust_stock(): void
    {
        $this->expectException(AuthorizationException::class);
        app(InventoryService::class)->adjust(User::factory()->create(), Product::factory()->create(), 5, 'Delivery received');
    }

    public function test_admin_stock_adjustment_is_audited(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $product = Product::factory()->create(['stock' => 5]);
        app(InventoryService::class)->adjust($user, $product, 3, 'Delivery received');
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseHas('activities', ['event' => 'stock.adjusted', 'subject' => $product->sku]);
    }

    public function test_staff_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $order = app(OrderService::class)->place($owner, $customer->id, [['product_id' => $product->id, 'quantity' => 1]]);
        $this->expectException(AuthorizationException::class);
        app(OrderService::class)->cancel($other, $order);
    }
}
