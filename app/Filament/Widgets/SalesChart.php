<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'Sales over the last 14 days';

    protected ?string $description = 'Confirmed order value in USD. Cancelled orders are excluded.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $values = [];
        for ($day = 13; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $labels[] = $date->format('M j');
            $values[] = Order::where('status', 'confirmed')->whereDate('created_at', $date->toDateString())->sum('total_cents') / 100;
        }

        return ['datasets' => [['label' => 'Confirmed sales (USD)', 'data' => $values, 'borderColor' => '#14b8a6', 'backgroundColor' => 'rgba(20,184,166,0.12)', 'fill' => true, 'tension' => 0.35]], 'labels' => $labels];
    }
}
