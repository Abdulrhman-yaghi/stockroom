<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ManageActivities;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationLabel = 'Activity history';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('event')->badge()->searchable(), TextColumn::make('subject')->searchable()->wrap(), TextColumn::make('user.name')->label('Actor'), TextColumn::make('details')->getStateUsing(fn ($record): string => json_encode($record->details, JSON_UNESCAPED_SLASHES))->wrap(), TextColumn::make('created_at')->dateTime('M j, H:i:s')])->filters([SelectFilter::make('event')->options(['order.confirmed' => 'Order confirmed', 'order.cancelled' => 'Order cancelled', 'stock.adjusted' => 'Stock adjusted', 'report.requested' => 'Report requested'])])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivities::route('/')];
    }
}
