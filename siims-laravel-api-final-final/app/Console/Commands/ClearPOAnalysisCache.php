<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearPOAnalysisCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-po-analysis 
                            {--coordinator= : Clear cache for specific coordinator ID}
                            {--section= : Clear cache for specific section ID}
                            {--week= : Clear cache for specific week number}
                            {--all : Clear all cache entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear PO analysis cache entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $coordinatorId = $this->option('coordinator');
        $sectionId = $this->option('section');
        $week = $this->option('week');
        $all = $this->option('all');

        if ($all) {
            // Clear all cache
            $deleted = DB::table('po_analysis_cache')->delete();
            $this->info("Cleared all PO analysis cache entries ({$deleted} entries deleted).");
            return 0;
        }

        // Build query based on options
        $query = DB::table('po_analysis_cache');

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
            $this->info("Filtering by coordinator: {$coordinatorId}");
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
            $this->info("Filtering by section: {$sectionId}");
        } else if ($coordinatorId && !$sectionId) {
            // If coordinator is specified but section is not, include both with and without section
            $query->where(function($q) {
                $q->whereNotNull('section_id')
                  ->orWhereNull('section_id');
            });
        }

        if ($week !== null) {
            if ($week === 'null' || $week === '') {
                $query->whereNull('week_number');
                $this->info("Filtering by week: Overall (null)");
            } else {
                $query->where('week_number', (int)$week);
                $this->info("Filtering by week: {$week}");
            }
        }

        // Count before deletion
        $count = $query->count();
        
        if ($count === 0) {
            $this->warn('No cache entries found matching the criteria.');
            return 0;
        }

        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete {$count} cache entry/entries?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Delete
        $deleted = $query->delete();
        
        $this->info("Successfully deleted {$deleted} cache entry/entries.");
        
        return 0;
    }
}

