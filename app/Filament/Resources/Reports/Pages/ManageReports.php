<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReports extends ManageRecords
{
    protected static string $resource = ReportResource::class;

    public function getSubheading(): ?string
    {
        return 'Confirmed orders only. Reports run in the background and are private to the requesting administrator.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Generate sales report')->createAnother(false)->using(fn (array $data): Report => app(ReportService::class)->request(auth()->user(), $data['from_date'], $data['to_date']))];
    }
}
