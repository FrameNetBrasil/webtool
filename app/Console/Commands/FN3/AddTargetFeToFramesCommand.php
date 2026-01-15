<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddTargetFeToFramesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:add-target-fe
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Target frame element to all frames where idNamespace <> 14';

    private array $stats = [
        'total_frames' => 0,
        'target_fe_created' => 0,
        'skipped_already_exists' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎯 Add Target Frame Element to Frames');
        $this->newLine();

        // Configuration
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user');

        $this->info('Configuration:');
        $this->line("  • User ID: {$userId}");
        $this->line("  • Frame Element Name: Target");
        $this->line("  • Core Type: cty_target");
        $this->line("  • Color ID: 1");
        if ($isDryRun) {
            $this->warn('  • DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Query frames where idNamespace <> 14
        $this->info('📚 Loading frames where idNamespace <> 14...');
        $frames = Criteria::table('frame')
            ->select('idFrame')
            ->where('idNamespace', '<>', 14)
            ->orderBy('idFrame')
            ->all();

        if (empty($frames)) {
            $this->error('❌ No frames found with idNamespace <> 14');

            return 1;
        }

        $this->stats['total_frames'] = count($frames);
        $this->info("Found {$this->stats['total_frames']} frames to process");
        $this->newLine();

        // Process frames
        $this->info('🔄 Creating Target frame elements...');
        $this->newLine();

        $this->withProgressBar($frames, function ($frame) use ($isDryRun, $userId) {
            $this->processFrame($frame->idFrame, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Display statistics
        $this->displayStatistics($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('✅ Dry run completed. Use without --dry-run to create frame elements.');
        } else {
            $this->newLine();
            $this->info('✅ Target frame elements created successfully!');
        }

        return 0;
    }

    private function processFrame(int $idFrame, bool $isDryRun, int $userId): void
    {
        try {
            // Check if Target FE already exists for this frame
            $existingFe = Criteria::table('frameelement')
                ->where('idFrame', '=', $idFrame)
                ->where('entry', '=', 'fe_target')
                ->first();

            if ($existingFe) {
                $this->stats['skipped_already_exists']++;

                return;
            }

            if (! $isDryRun) {
                // Call fe_create(idFrame, 'Target', 'cty_target', 1, userId)
                $result = DB::select(
                    'SELECT fe_create(?, ?, ?, ?, ?) as idFrameElement',
                    [$idFrame, 'Target', 'cty_target', 1, $userId]
                );

                if (! empty($result) && $result[0]->idFrameElement) {
                    $this->stats['target_fe_created']++;
                }
            } else {
                $this->stats['target_fe_created']++;
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Statistics:');
        $this->newLine();

        $tableData = [
            ['Total frames processed', $this->stats['total_frames']],
            ['Target FEs created', $this->stats['target_fe_created'].($isDryRun ? ' (would be created)' : '')],
            ['Skipped (already exists)', $this->stats['skipped_already_exists']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['skipped_already_exists'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_already_exists']} frames already have a Target frame element");
        }

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->error("❌ {$this->stats['errors']} frame elements failed to create due to errors");
        }
    }
}
