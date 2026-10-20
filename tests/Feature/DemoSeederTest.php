<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_fictional_and_supports_a_reviewer_login(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(8, Item::count());
        $this->assertSame(14, StockIn::count());
        $this->assertSame(188, Sale::count());
        $user = User::sole();
        $this->assertSame('reviewer@example.test', $user->email);
        $this->assertTrue(Hash::check('PortfolioDemo2026!', $user->password));
        $this->assertTrue(Item::all()->every(fn ($item) => str_starts_with($item->name, 'Demo ')));
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('all products, prices and transactions are fictional');
    }

    public function test_demo_seeding_refuses_to_overwrite_an_existing_database(): void
    {
        User::factory()->create();
        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Expected an occupied database to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('empty, separate demo database', $exception->getMessage());
        }
        $this->assertSame(1, User::count());
        $this->assertSame(0, Item::count());
    }
}
