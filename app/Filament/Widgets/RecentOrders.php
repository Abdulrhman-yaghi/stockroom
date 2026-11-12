<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent orders';

    public function table(Table $table): Table
    {
        return $table->query(Order::with('customer')->latest()->limit(5))->paginated(false)->columns([TextColumn::make('reference')->label('Order')->limit(18), TextColumn::make('customer.name')->label('Customer'), TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'confirmed' ? 'success' : 'gray'), TextColumn::make('total_cents')->label('Total')->money('USD', divideBy: 100), TextColumn::make('created_at')->dateTime('M j, H:i')]);
    }
}
