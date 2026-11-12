<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /** @param array<int, array{product_id: int, quantity: int}> $items */
    public function place(User $actor, int $customerId, array $items): Order
    {
        Gate::forUser($actor)->authorize('create', Order::class);
        Validator::make(['customer_id' => $customerId, 'items' => $items], ['customer_id' => 'required|exists:customers,id', 'items' => 'required|array|min:1|max:30', 'items.*.product_id' => 'required|integer|distinct|exists:products,id', 'items.*.quantity' => 'required|integer|min:1|max:10000'])->validate();
        usort($items, fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);

        return DB::transaction(function () use ($actor, $customerId, $items): Order {
            $order = Order::create(['reference' => 'SR-'.Str::upper((string) Str::ulid()), 'customer_id' => $customerId, 'user_id' => $actor->id, 'total_cents' => 0, 'status' => 'confirmed']);
            $total = 0;
            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $changed = Product::whereKey($product->id)->where('active', true)->where('stock', '>=', $quantity)->decrement('stock', $quantity);
                if ($changed !== 1) {
                    throw ValidationException::withMessages(['items' => 'Insufficient stock or inactive product: '.$product->name]);
                }
                $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $quantity, 'unit_price_cents' => $product->price_cents]);
                $total += $product->price_cents * $quantity;
            }
            $order->update(['total_cents' => $total]);
            Activity::create(['user_id' => $actor->id, 'event' => 'order.confirmed', 'subject' => $order->reference, 'details' => ['total_cents' => $total, 'lines' => count($items)]]);

            return $order;
        }, 5);
    }

    public function cancel(User $actor, Order $order): void
    {
        Gate::forUser($actor)->authorize('cancel', $order);
        DB::transaction(function () use ($actor, $order): void {
            $changed = Order::whereKey($order->id)->where('status', 'confirmed')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            if ($changed === 0) {
                return;
            }
            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
            Activity::create(['user_id' => $actor->id, 'event' => 'order.cancelled', 'subject' => $order->reference, 'details' => ['stock_restored' => true]]);
        }, 5);
    }
}
