<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WeeklyEntry extends Model
{
    use HasFactory;

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'application_id',
        'week_number',
        'start_date',
        'end_date',
        'tasks',
        'learnings',
        'no_of_hours',
        'student_id',
    ];

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when a weekly entry is created, updated, or deleted
        static::created(function ($weeklyEntry) {
            $weeklyEntry->invalidateCache();
        });

        static::updated(function ($weeklyEntry) {
            $weeklyEntry->invalidateCache();
        });

        static::deleted(function ($weeklyEntry) {
            $weeklyEntry->invalidateCache();
        });
    }

    /**
     * Relationship to Application
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Relationship to Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Invalidate PO analysis cache for this weekly entry's coordinator and section
     */
    public function invalidateCache()
    {
        try {
            // Get the student - try both through application and direct relationship
            $student = null;
            
            if ($this->application) {
                $student = $this->application->student;
            } elseif ($this->student_id) {
                $student = $this->student;
            }
            
            if (!$student) {
                Log::warning('WeeklyEntry: Cannot invalidate cache - no student found', [
                    'weekly_entry_id' => $this->id,
                    'application_id' => $this->application_id,
                    'student_id' => $this->student_id
                ]);
                return;
            }

            $coordinatorId = $student->coordinator_id;
            $sectionId = $student->section_id;
            $weekNumber = $this->week_number;

            if (!$coordinatorId) {
                Log::warning('WeeklyEntry: Cannot invalidate cache - no coordinator_id found', [
                    'weekly_entry_id' => $this->id,
                    'student_id' => $student->id
                ]);
                return;
            }

            // Build cache invalidation query
            $query = DB::table('po_analysis_cache')
                ->where('coordinator_id', $coordinatorId);

            // Include section if available
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            } else {
                $query->whereNull('section_id');
            }

            // Include week if available
            // Invalidate cache for both the specific week and overall (null week)
            // This ensures cache is cleared for both per-week and overall views
            $query->where(function($q) use ($weekNumber) {
                if ($weekNumber) {
                    $q->where('week_number', $weekNumber);
                }
                // Also invalidate overall cache (null week) since adding/updating/deleting
                // a weekly entry affects the overall analysis
                $q->orWhereNull('week_number');
            });

            // Delete matching cache entries
            $deleted = $query->delete();

            Log::info('WeeklyEntry: Invalidated PO analysis cache', [
                'weekly_entry_id' => $this->id,
                'coordinator_id' => $coordinatorId,
                'section_id' => $sectionId,
                'week_number' => $weekNumber,
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('WeeklyEntry: Error invalidating cache', [
                'weekly_entry_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
