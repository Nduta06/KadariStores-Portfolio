<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacySqliteDataTest extends TestCase
{
    private ?string $sourcePath = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('MYSQL_TEST_DATABASE') !== 'inventory_migration_test') {
            $this->markTestSkipped('Requires the disposable inventory_migration_test MySQL database.');
        }
        config(['database.connections.mysql.database' => 'inventory_migration_test']);
        DB::purge('mysql');
        $this->artisan('migrate:fresh', ['--database' => 'mysql', '--force' => true])->assertExitCode(0);
        $this->sourcePath = tempnam(sys_get_temp_dir(), 'inventory-source-');
        config(['database.connections.legacy_fixture' => [
            'driver' => 'sqlite', 'database' => $this->sourcePath,
            'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        $this->artisan('migrate', ['--database' => 'legacy_fixture', '--force' => true])->assertExitCode(0);
        User::factory()->connection('legacy_fixture')->create(['id' => 25]);
        $item = Item::factory()->connection('legacy_fixture')->create(['id' => 40]);
        StockIn::factory()->connection('legacy_fixture')->create(['item_id' => $item->id]);
        Sale::factory()->connection('legacy_fixture')->create(['item_id' => $item->id]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('legacy_fixture');
        DB::disconnect('legacy_sqlite');
        if ($this->sourcePath !== null) {
            unlink($this->sourcePath);
        }
        parent::tearDown();
    }

    public function test_it_preserves_records_password_hashes_and_mysql_next_ids(): void
    {
        $this->artisan('data:import-sqlite', ['path' => $this->sourcePath, '--connection' => 'mysql'])->assertExitCode(0);
        foreach (['users', 'items', 'stock_ins', 'sales'] as $table) {
            $source = DB::connection('legacy_fixture')->table($table)->first();
            $target = DB::connection('mysql')->table($table)->first();
            $this->assertNotNull($target);
            foreach ((array) $source as $column => $value) {
                if ($column === 'date') {
                    $this->assertSame(Carbon::parse($value)->toDateString(), $target->{$column}, "{$table}.{$column}");

                    continue;
                }
                $this->assertEquals($value, $target->{$column}, "{$table}.{$column}");
            }
        }
        $newUser = User::factory()->connection('mysql')->create();
        $this->assertGreaterThan(25, $newUser->id);
    }

    public function test_it_refuses_to_overwrite_an_existing_destination(): void
    {
        $existing = User::factory()->connection('mysql')->create();
        $this->artisan('data:import-sqlite', ['path' => $this->sourcePath, '--connection' => 'mysql'])->assertExitCode(1);
        $this->assertSame(1, DB::connection('mysql')->table('users')->count());
        $this->assertSame($existing->email, DB::connection('mysql')->table('users')->first()->email);
        $this->assertSame(0, DB::connection('mysql')->table('items')->count());
    }

    public function test_it_rolls_back_every_table_when_a_sale_has_an_invalid_item(): void
    {
        DB::connection('legacy_fixture')->table('sales')->update(['item_id' => 999999]);
        $this->artisan('data:import-sqlite', ['path' => $this->sourcePath, '--connection' => 'mysql'])->assertExitCode(1);
        foreach (['users', 'items', 'stock_ins', 'sales'] as $table) {
            $this->assertSame(0, DB::connection('mysql')->table($table)->count());
        }
    }
}
