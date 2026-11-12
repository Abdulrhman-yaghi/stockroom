<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(User $actor, Product $product, int $delta, string $reason): void
    {
        Gate::forUser($actor)->authorize('update', $product);
        Validator::make(['delta' => $delta, 'reason' => $reason], ['delta' => 'required|integer|not_in:0|between:-100000,100000', 'reason' => 'required|string|min:5|max:255'])->validate();
        DB::transaction(function () use ($actor, $product, $delta, $reason): void {
            $query = Product::whereKey($product->id);
            if ($delta < 0) {
                $query->where('stock', '>=', abs($delta));
            }
            if ($query->increment('stock', $delta) !== 1) {
                throw ValidationException::withMessages(['delta' => 'This adjustment would make stock negative.']);
            }
            Activity::create(['user_id' => $actor->id, 'event' => 'stock.adjusted', 'subject' => $product->sku, 'details' => ['delta' => $delta, 'reason' => $reason]]);
        }, 5);
    }
}
