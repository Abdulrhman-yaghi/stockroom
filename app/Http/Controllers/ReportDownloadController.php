<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    public function __invoke(Report $report): StreamedResponse
    {
        Gate::authorize('view', $report);
        abort_unless($report->status === 'ready' && $report->path && Storage::disk('local')->exists($report->path), 404);

        return Storage::disk('local')->download($report->path, 'stockroom-sales-'.$report->id.'.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'private, no-store']);
    }
}
