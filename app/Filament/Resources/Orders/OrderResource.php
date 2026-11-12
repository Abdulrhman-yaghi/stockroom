<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Customer')->options(fn () => Customer::pluck('name', 'id'))->searchable()->required()->columnSpanFull(),
            Repeater::make('items')->schema([Select::make('product_id')->label('Product')->options(fn () => Product::where('active', true)->get()->mapWithKeys(fn (Product $p): array => [$p->id => $p->name.' | $'.number_format($p->price_cents / 100, 2).' | '.$p->stock.' in stock']))->searchable()->required()->distinct(), TextInput::make('quantity')->integer()->minValue(1)->maxValue(10000)->default(1)->required()])->columns(2)->minItems(1)->maxItems(30)->defaultItems(1)->addActionLabel('Add another product')->columnSpanFull()->helperText('Stock and prices are checked again when the order is confirmed. Duplicate products are not allowed.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference')->label('Order')->searchable()->copyable()->limit(17)->tooltip(fn (Order $record): string => $record->reference), TextColumn::make('customer.name')->searchable(), TextColumn::make('user.name')->label('Placed by'),
            TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'confirmed' ? 'success' : 'gray'), TextColumn::make('total_cents')->label('Total')->money('USD', divideBy: 100)->sortable(), TextColumn::make('created_at')->dateTime('M j, H:i')->sortable(),
        ])->filters([SelectFilter::make('status')->options(['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'])])->recordActions([
            Action::make('details')->label('Details')->modalHeading('Order breakdown')->modalContent(fn (Order $record) => view('filament.order-details', ['order' => $record->load('items')]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            Action::make('cancel')->label('Cancel order')->color('danger')->authorize('cancel')->visible(fn (Order $record): bool => $record->status === 'confirmed')->requiresConfirmation()->modalDescription('This restores every item to stock. An order can only be cancelled once.')->action(fn (Order $record) => app(OrderService::class)->cancel(auth()->user(), $record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrders::route('/')];
    }
}
