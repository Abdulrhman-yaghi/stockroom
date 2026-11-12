<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(120), TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->maxLength(190), TextInput::make('company')->maxLength(120)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('company')->searchable(), TextColumn::make('email')->searchable(), TextColumn::make('created_at')->date()->label('Added')])->recordActions([EditAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCustomers::route('/')];
    }
}
