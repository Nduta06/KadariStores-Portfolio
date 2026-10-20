<?php

namespace Tests\Feature\Items;

use App\Livewire\Items\Manager;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_items_page(): void
    {
        $this->get('/items')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_items_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/items')->assertOk()->assertSeeLivewire(Manager::class);
    }

    public function test_it_lists_existing_items(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['name' => 'Basmat']);

        Livewire::test(Manager::class)
            ->assertSee('Basmat');
    }

    public function test_it_can_create_an_item(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('name', 'Njahi')
            ->set('category', 'Cereal')
            ->set('unit', 'Kg')
            ->set('buying_price', '80')
            ->set('selling_price', '160')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('items', [
            'name' => 'Njahi',
            'buying_price' => 80,
            'selling_price' => 160,
        ]);
    }

    public function test_it_requires_a_unique_item_name(): void
    {
        $this->actingAs(User::factory()->create());
        Item::factory()->create(['name' => 'Basmat']);

        Livewire::test(Manager::class)
            ->set('name', 'Basmat')
            ->set('category', 'Cereal')
            ->set('unit', 'Kg')
            ->set('buying_price', '80')
            ->set('selling_price', '160')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_it_can_update_an_existing_item(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 100]);

        Livewire::test(Manager::class)
            ->call('edit', $item->id)
            ->set('selling_price', '150')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(150, $item->fresh()->selling_price);
    }

    public function test_it_can_delete_an_item_with_no_history(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();

        Livewire::test(Manager::class)
            ->call('delete', $item->id);

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_eggs_per_tray_is_optional_and_only_needed_for_piece_sales(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('name', 'Eggs')
            ->set('category', 'Eggs')
            ->set('unit', 'Tray')
            ->set('buying_price', '410')
            ->set('selling_price', '450')
            ->set('eggs_per_tray', '30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('items', ['name' => 'Eggs', 'eggs_per_tray' => 30]);
    }
}
