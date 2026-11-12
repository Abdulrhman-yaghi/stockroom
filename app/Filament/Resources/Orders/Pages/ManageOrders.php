<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Place order')->modalHeading('Place a new order')->createAnother(false)->using(fn (array $data): Order => app(OrderService::class)->place(auth()->user(), (int) $data['customer_id'], $data['items']))];
    }
}
