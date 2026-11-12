<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateSalesReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $reportId) {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('report-'.$this->reportId))->releaseAfter(10)->expireAfter(90)];
    }

    public function handle(): void
    {
        $report = Report::findOrFail($this->reportId);
        if ($report->status === 'ready') {
            return;
        }
        $report->update(['status' => 'processing']);
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Order', 'Customer', 'Company', 'Placed at UTC', 'Status', 'Total USD'], ',', '"', '');
        $count = 0;
        foreach (Order::with('customer')->where('status', 'confirmed')->whereBetween('created_at', [$report->from_date->startOfDay(), $report->to_date->endOfDay()])->orderBy('id')->lazyById(200) as $order) {
            fputcsv($stream, [$order->reference, self::safeCell($order->customer->name), self::safeCell($order->customer->company ?? ''), $order->created_at->toDateTimeString(), $order->status, number_format($order->total_cents / 100, 2, '.', '')], ',', '"', '');
            $count++;
        }
        rewind($stream);
        $path = 'reports/'.$report->id.'.csv';
        try {
            if (! Storage::disk('local')->put($path, $stream)) {
                throw new RuntimeException('Could not write report.');
            }
        } finally {
            fclose($stream);
        }
        $report->update(['status' => 'ready', 'path' => $path, 'row_count' => $count]);
    }

    public function failed(?Throwable $exception): void
    {
        Report::whereKey($this->reportId)->update(['status' => 'failed']);
    }

    private static function safeCell(string $value): string
    {
        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
    }
}
