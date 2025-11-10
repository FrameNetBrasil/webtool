<?php

namespace App\Console\Commands;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestDatabaseConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:test-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connectivity to all configured databases (webtool, webtool42, mfn41)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Database Connections...');
        $this->newLine();

        $databases = [
            'webtool' => 'Main Application Database',
            'webtool42' => 'Webtool 4.2 Database',
            'mfn41' => 'Multimodal FrameNet 4.1 Database',
        ];

        $allSuccess = true;

        foreach ($databases as $connection => $label) {
            $allSuccess = $this->testConnection($connection, $label) && $allSuccess;
            $this->newLine();
        }

        if ($allSuccess) {
            $this->info('✓ All database connections successful!');
            return Command::SUCCESS;
        } else {
            $this->error('✗ Some database connections failed. Check your .env configuration.');
            return Command::FAILURE;
        }
    }

    /**
     * Test a specific database connection.
     */
    protected function testConnection(string $connection, string $label): bool
    {
        $this->line("Testing <fg=cyan>{$label}</> (<fg=yellow>{$connection}</>):");

        try {
            // Set the database connection for Criteria
            if ($connection === 'webtool') {
                Criteria::$database = '';  // Default connection
            } else {
                Criteria::$database = $connection;
            }

            // Test 1: Basic Connection Test
            $this->line('  → Checking connection...');
            DB::connection($connection)->getPdo();
            $this->line('    <fg=green>✓</> Connection established');

            // Test 2: Get Database Name
            $dbName = DB::connection($connection)->getDatabaseName();
            $this->line("    <fg=green>✓</> Database: {$dbName}");

            // Test 3: Test database function
            $this->line('  → Testing database function...');
            $result = DB::connection($connection)->selectOne('SELECT DATABASE() as dbname');
            $this->line("    <fg=green>✓</> Database function working - Current DB: {$result->dbname}");

            // Test 4: Test Criteria query builder
            $this->line('  → Testing Criteria query builder...');
            $tables = DB::connection($connection)->select('SHOW TABLES');
            if (count($tables) > 0) {
                // Get first table name to test Criteria
                $firstTableKey = "Tables_in_{$dbName}";
                $firstTable = $tables[0]->$firstTableKey;

                // Try a simple Criteria query
                $count = Criteria::table($firstTable)->count();
                $this->line("    <fg=green>✓</> Criteria query successful - Table '{$firstTable}' has {$count} records");
            } else {
                $this->line("    <fg=yellow>⚠</> No tables found to test Criteria");
            }

            // Test 5: Try to list tables
            $this->line('  → Listing tables...');
            $tables = DB::connection($connection)
                ->select('SHOW TABLES');
            $tableCount = count($tables);
            $this->line("    <fg=green>✓</> Found {$tableCount} tables");

            // Show first few tables as sample
            if ($tableCount > 0) {
                $sampleTables = array_slice($tables, 0, 5);
                $tableNames = array_map(function ($table) use ($dbName) {
                    $key = "Tables_in_{$dbName}";
                    return $table->$key;
                }, $sampleTables);
                $this->line('    Sample tables: ' . implode(', ', $tableNames));
            }

            $this->line("  <fg=green>✓ {$label} - ALL TESTS PASSED</>");
            return true;

        } catch (\Exception $e) {
            $this->line("  <fg=red>✗ {$label} - FAILED</>");
            $this->error('    Error: ' . $e->getMessage());
            return false;
        } finally {
            // Reset Criteria database to default
            Criteria::$database = '';
        }
    }
}
