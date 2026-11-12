<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_administrator_can_open_every_dashboard_screen(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $this->actingAs($user);
        foreach (['/admin', '/admin/guide', '/admin/products', '/admin/customers', '/admin/orders', '/admin/reports', '/admin/activities'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_staff_cannot_open_restricted_screens(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/reports')->assertForbidden();
        $this->get('/admin/activities')->assertForbidden();
    }

    public function test_order_form_places_order_through_inventory_service(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 4]);
        Livewire::test(ManageOrders::class)->callAction('create', data: ['customer_id' => $customer->id, 'items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertHasNoActionErrors();
        $this->assertSame(1, Order::count());
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_admin_adjustment_action_uses_audited_service(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $this->actingAs($user);
        $product = Product::factory()->create(['stock' => 4]);
        Livewire::test(ManageProducts::class)->callAction(TestAction::make('adjust')->table($product), data: ['delta' => 3, 'reason' => 'Delivery received'])->assertHasNoActionErrors();
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseHas('activities', ['event' => 'stock.adjusted']);
    }

    public function test_staff_does_not_have_product_mutation_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();
        Livewire::test(ManageProducts::class)->assertActionHidden('create')->assertActionHidden(TestAction::make('adjust')->table($product));
    }
}
