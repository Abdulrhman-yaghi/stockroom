<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Overview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Confirmed sales', 'USD '.number_format(Order::where('status', 'confirmed')->sum('total_cents') / 100, 2))->description('All time, excluding cancelled orders')->color('success'),
            Stat::make('Orders', Order::count())->description(Order::where('status', 'confirmed')->count().' confirmed orders')->color('primary'),
            Stat::make('Low stock', Product::where('active', true)->whereColumn('stock', '<=', 'reorder_level')->count())->description('Active products at their reorder level')->color('warning'),
            Stat::make('Customers', Customer::count())->description('Fictional demo businesses')->color('info'),
        ];
    }
}
