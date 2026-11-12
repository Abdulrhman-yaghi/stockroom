<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
try {
    app(OrderService::class)->place(User::firstOrFail(), Customer::firstOrFail()->id, [['product_id' => Product::firstOrFail()->id, 'quantity' => 1]]);
    echo 'placed';
    exit(0);
} catch (ValidationException $e) {
    echo 'out of stock';
    exit(2);
}
