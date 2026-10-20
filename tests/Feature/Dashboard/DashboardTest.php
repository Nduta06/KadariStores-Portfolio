<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\Overview;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertOk()->assertSeeLivewire(Overview::class);
    }

    public function test_it_totals_revenue_cost_and_profit_for_the_selected_period(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 100, 'buying_price' => 60]);

        Sale::create(Sale::computeAttributes($item, now()->format('Y-m-d'), 1, null, null, null));
        Sale::create(Sale::computeAttributes($item, now()->format('Y-m-d'), 2, null, null, null));

        Livewire::test(Overview::class)
            ->set('period', 'all')
            ->assertSet('revenue', 300.0)
            ->assertSet('cogs', 180.0)
            ->assertSet('profit', 120.0);
    }

    public function test_the_today_period_excludes_older_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 100, 'buying_price' => 60]);

        Sale::create(Sale::computeAttributes($item, now()->format('Y-m-d'), 1, null, null, null));
        Sale::create(Sale::computeAttributes($item, '2020-01-01', 1, null, null, null));

        Livewire::test(Overview::class)
            ->set('period', 'today')
            ->assertSet('revenue', 100.0);
    }

    public function test_it_shows_the_revenue_trend_table(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['name' => 'Basmat', 'selling_price' => 100, 'buying_price' => 60]);
        Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        Livewire::test(Overview::class)
            ->set('rollup', 'monthly')
            ->assertSee('Basmat')
            ->assertSee('Aug 2026');
    }

    public function test_it_links_low_and_out_of_stock_counts_to_the_stock_balance_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Overview::class)
            ->assertSeeHtml(route('stock-balance.index'));
    }
}
