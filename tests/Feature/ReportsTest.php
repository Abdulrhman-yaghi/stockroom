<?php

namespace Tests\Feature;

use App\Jobs\GenerateSalesReport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    public function test_report_contains_confirmed_orders_and_escapes_spreadsheet_formulas(): void
    {
        Queue::fake();
        Storage::fake('local');
        $user = $this->administrator();
        $customer = Customer::factory()->create(['name' => '=FORMULA()']);
        $product = Product::factory()->create(['price_cents' => 1234]);
        app(OrderService::class)->place($user, $customer->id, [['product_id' => $product->id, 'quantity' => 2]]);
        $cancelled = app(OrderService::class)->place($user, $customer->id, [['product_id' => $product->id, 'quantity' => 1]]);
        app(OrderService::class)->cancel($user, $cancelled);
        $report = app(ReportService::class)->request($user, now()->toDateString(), now()->toDateString());
        (new GenerateSalesReport($report->id))->handle();
        $report->refresh();
        $this->assertSame('ready', $report->status);
        $this->assertSame(1, $report->row_count);
        $csv = Storage::get($report->path);
        $this->assertStringContainsString("'=FORMULA()", $csv);
        $this->assertStringContainsString('24.68', $csv);
        $this->assertStringNotContainsString($cancelled->reference, $csv);
        (new GenerateSalesReport($report->id))->handle();
        $this->assertSame($csv, Storage::get($report->path));
    }

    public function test_report_download_requires_owning_admin(): void
    {
        Storage::fake('local');
        $owner = $this->administrator();
        $other = $this->administrator();
        $staff = User::factory()->create();
        $report = Report::create(['user_id' => $owner->id, 'from_date' => '2026-01-01', 'to_date' => '2026-01-31', 'status' => 'ready', 'path' => 'reports/sample.csv']);
        Storage::put($report->path, 'test');
        $url = route('reports.download', $report);
        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($staff)->get($url)->assertForbidden();
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertDownload('stockroom-sales-'.$report->id.'.csv');
    }

    public function test_staff_cannot_request_reports(): void
    {
        $this->expectException(AuthorizationException::class);
        app(ReportService::class)->request(User::factory()->create(), '2026-01-01', '2026-01-31');
    }

    public function test_report_rejects_reversed_dates(): void
    {
        $this->expectException(ValidationException::class);
        app(ReportService::class)->request($this->administrator(), '2026-02-01', '2026-01-01');
    }

    public function test_date_range_excludes_orders_outside_window(): void
    {
        Queue::fake();
        Storage::fake('local');
        $user = $this->administrator();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        app(OrderService::class)->place($user, $customer->id, [['product_id' => $product->id, 'quantity' => 1]]);
        $report = app(ReportService::class)->request($user, '2020-01-01', '2020-01-02');
        (new GenerateSalesReport($report->id))->handle();
        $this->assertSame(0, $report->fresh()->row_count);
    }

    public function test_failed_job_can_be_requeued_by_its_owner(): void
    {
        Queue::fake();
        $owner = $this->administrator();
        $report = Report::create(['user_id' => $owner->id, 'from_date' => '2026-01-01', 'to_date' => '2026-01-31', 'status' => 'processing']);
        (new GenerateSalesReport($report->id))->failed(new \RuntimeException('test'));
        $this->assertSame('failed', $report->fresh()->status);
        app(ReportService::class)->retry($owner, $report->fresh());
        $this->assertSame('queued', $report->fresh()->status);
        Queue::assertPushed(GenerateSalesReport::class);
    }
}
