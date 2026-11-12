<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120), TextInput::make('sku')->label('SKU')->required()->unique(ignoreRecord: true)->maxLength(50),
            TextInput::make('price_cents')->label('Price in cents (USD)')->helperText('2500 means $25.00. Prices are stored as integers.')->integer()->minValue(1)->maxValue(100000000)->required(),
            TextInput::make('reorder_level')->integer()->minValue(0)->maxValue(100000)->default(5)->required(), Toggle::make('active')->default(true)->helperText('Inactive products cannot be ordered. Use Adjust stock after creating a product.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable()->description(fn (Product $record): string => $record->sku),
            TextColumn::make('price_cents')->label('Unit price')->money('USD', divideBy: 100)->sortable(),
            TextColumn::make('stock')->label('Available')->badge()->color(fn (Product $record): string => $record->stock <= $record->reorder_level ? 'warning' : 'success')->sortable(),
            TextColumn::make('reorder_level')->label('Reorder at'), IconColumn::make('active')->boolean(),
        ])->filters([Filter::make('low_stock')->label('Low stock only')->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'reorder_level'))])
            ->recordActions([EditAction::make(), Action::make('adjust')->label('Adjust stock')->icon(Heroicon::OutlinedArrowsUpDown)->authorize('update')->schema([
                TextInput::make('delta')->label('Quantity change')->helperText('Positive to receive stock. Negative to remove stock.')->integer()->required()->minValue(-100000)->maxValue(100000)->rules(['not_in:0']),
                TextInput::make('reason')->required()->minLength(5)->maxLength(255),
            ])->action(fn (Product $record, array $data) => app(InventoryService::class)->adjust(auth()->user(), $record, (int) $data['delta'], $data['reason']))])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => ManageProducts::route('/')];
    }
}
