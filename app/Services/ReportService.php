<?php

namespace App\Services;

use App\Jobs\GenerateSalesReport;
use App\Models\Activity;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ReportService
{
    public function request(User $user, string $from, string $to): Report
    {
        Gate::forUser($user)->authorize('create', Report::class);
        Validator::make(['from' => $from, 'to' => $to], ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'])->validate();

        return DB::transaction(function () use ($user, $from, $to): Report {
            $report = Report::create(['user_id' => $user->id, 'from_date' => $from, 'to_date' => $to, 'status' => 'queued']);
            Activity::create(['user_id' => $user->id, 'event' => 'report.requested', 'subject' => 'Report #'.$report->id, 'details' => ['from' => $from, 'to' => $to]]);
            GenerateSalesReport::dispatch($report->id)->afterCommit();

            return $report;
        });
    }

    public function retry(User $user, Report $report): void
    {
        Gate::forUser($user)->authorize('view', $report);
        if (Report::whereKey($report->id)->where('status', 'failed')->update(['status' => 'queued']) === 1) {
            GenerateSalesReport::dispatch($report->id);
        }
    }
}
