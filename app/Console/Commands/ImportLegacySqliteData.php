<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacySqliteData extends Command
{
    protected $signature = 'data:import-sqlite
        {path : Absolute path to the old database.sqlite file}
        {--connection= : Destination connection to import into (defaults to the app\'s default connection)}';

    protected $description = 'Copy shop data (users, items, stock-ins, sales) from an old SQLite database file into the current database, preserving IDs, so it does not need to be re-entered after switching database engines.';

    /** Import order matters: items before the tables that reference item_id. */
    private const TABLES = ['users', 'items', 'stock_ins', 'sales'];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("No SQLite file found at: {$path}");

            return self::FAILURE;
        }

        $destinationName = $this->option('connection') ?: config('database.default');

        config(['database.connections.legacy_sqlite' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        DB::purge('legacy_sqlite');
        $source = DB::connection('legacy_sqlite');
        $target = DB::connection($destinationName);
        if (! in_array($target->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('The destination must be MySQL or MariaDB.');

            return self::FAILURE;
        }

        if ($destinationName === 'legacy_sqlite') {
            $this->error('Source and destination connections must be different.');

            return self::FAILURE;
        }

        /** Validate all tables before writing anything. */
        foreach (self::TABLES as $table) {
            if (! Schema::connection('legacy_sqlite')->hasTable($table)) {
                $this->error("The source is missing {$table}; import cancelled.");

                return self::FAILURE;
            }

            if (! Schema::connection($destinationName)->hasTable($table)) {
                $this->error("The destination is missing {$table}; run migrations first.");

                return self::FAILURE;
            }

            if ($target->table($table)->exists()) {
                $this->error("Destination {$table} is not empty; no data was changed.");

                return self::FAILURE;
            }
        }

        $this->info("Importing from [{$path}] into the '{$destinationName}' connection...");

        try {
            $target->transaction(function () use ($source, $target): void {
                foreach (self::TABLES as $table) {
                    $source->table($table)->orderBy('id')->chunk(200, function ($rows) use ($target, $table): void {
                        $target->table($table)->insert($rows->map(fn ($row) => (array) $row)->all());
                    });

                    $this->info("  {$table}: imported.");
                }
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Import failed and all inserted rows were rolled back. Check the application log.');

            return self::FAILURE;
        } finally {
            DB::disconnect('legacy_sqlite');
        }

        $this->info('Done. Your existing items, stock-ins, sales and login are now in the new database.');

        return self::SUCCESS;
    }
}
