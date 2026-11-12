<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\Report;
use App\Services\ReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([DatePicker::make('from_date')->label('From date (UTC)')->default(now()->startOfMonth())->required(), DatePicker::make('to_date')->label('Through date (UTC)')->default(now())->afterOrEqual('from_date')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('Report #'), TextColumn::make('from_date')->date(), TextColumn::make('to_date')->date(), TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'ready' => 'success','failed' => 'danger',default => 'warning'
            }), TextColumn::make('row_count')->label('Orders'), TextColumn::make('created_at')->dateTime('M j, H:i'),
        ])->recordActions([Action::make('download')->label('Download CSV')->authorize('view')->visible(fn (Report $record): bool => $record->status === 'ready')->url(fn (Report $record): string => route('reports.download', $record)), Action::make('retry')->authorize('view')->visible(fn (Report $record): bool => $record->status === 'failed')->action(fn (Report $record) => app(ReportService::class)->retry(auth()->user(), $record))])->poll('5s')->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageReports::route('/')];
    }
}
