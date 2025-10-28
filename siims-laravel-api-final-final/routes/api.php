<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\DtrController;

use App\Http\Controllers\LogController;
use App\Http\Controllers\MessagingController;

use App\Http\Controllers\StatusController;

use App\Http\Controllers\TestController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\WorkTypeController;
use App\Models\Application;
use App\Models\WeeklyEntry;
use App\Models\WorkType;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CoordinatorOpenAISummaryController;

/* Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {

    $user = $request->user();
    return $user;
}); */

// Version 1 Api
Route::prefix('/v1')->group(function () {

    // Router Auth Prefix
    Route::prefix('/auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
        Route::post('/password-reset', [AuthController::class, 'resetPassword']);
        Route::post('/register', [AuthController::class, 'regiser']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);
    });



    // Router Authentication
    Route::middleware(['auth:sanctum'])->group(function () {

        /**
         * Route name: Dashboard Routes
         * API Route: Dashboard API Portal
         * Roles Allowed: Admin, Dean, Chairperson, Supervisor, OSA, Company, Coordinator
         */
        require base_path('routes/dashboards.php');

        /**
         * Route name: Document Type Routes
         * API Route: Document Type API Portal
         * Roles Allowed: Admin, OSA
         */
        require base_path('routes/document-types.php');

        /**
         * Route name: Sections Routes
         * API Route: Sections API Portal
         * Roles Allowed: Admin
         */
        require base_path('routes/sections.php');

        /**
         * Route name: Certificates Routes
         * API Route: Certificates API Portal
         * Roles Allowed: Student
         */
        require base_path('routes/certificates.php');

        /**
         * Route name: Document Tracking
         * API Route: Document Tracking API Portal
         * Roles Allowed: Student
         */
        require base_path('routes/trackings.php');

        /**
         * Route name: Role Routes
         * API Route: Role API Portal
         * Roles Allowed: Admin
         */
        require base_path('routes/roles.php');

        /**
         * Route name: College Routes
         * API Route: College API Poral
         * Roles Allowed: All
         */
        require base_path('routes/colleges.php');

        /**
         * Route name: Program Routes
         * API Route: Program API Portal
         * Roles Allowed: All
         */
        require base_path('routes/programs.php');

        /**
         * Route name: User Routes
         * API Route: User API Portal
         * Roles Allowed: All
         */
        require base_path('routes/users.php');

        /**
         * Route name: Profile Routes
         * API Route: Profile API Portal
         * Roles Allowed: All
         */
        require base_path('routes/profiles.php');

        /**
         * Route name: Work Experiences Routes
         * API Route: Work Experiences API Portal
         * Roles Allowed: All
         */
        require base_path('routes/work-experiences.php');

         /**
         * Route name: Educations Routes
         * API Route: Educations API Portal
         * Roles Allowed: Admin, Student
         */
        require base_path('routes/educations.php');

         /**
         * Route name: Endorsement Letter Requests Routes
         * API Route: Endorsement Letter Requests API Portal
         * Roles Allowed: Admin, Student, Dean, Chairperson
         */
        require base_path('routes/endorsement-letter-requests.php');

        /**
         * Route name: Statuses Routes
         * API Route: Statuses API Portal
         * Roles Allowed: All
         */
        require base_path('routes/statuses.php');

        /**
         * Route name: Applications Routes
         * API Route: Applications API Portal
         * Roles Allowed: TBD
         */
        require base_path('routes/applications.php');

        /**
         * Route name: Weekly Accomplishment Entry Routes
         * API Route: Weekly Accomplishment Entry API Portal
         * Roles Allowed: All
         */
        require base_path('routes/war.php');

        /**
         * Route name: Reports Entry Routes
         * API Route: Reports Entry API Portal
         * Roles Allowed: Admin, Company, Supervisor, Student
         */
        require base_path('routes/reports.php');
        
        /**
         * Route name: Daily Time Record Entry Routes
         * API Route: Daily Time Record Entry API Portal
         * Roles Allowed: Admin, Student, Coordinator
         */
        require base_path('routes/daily-time-records.php');

         /**
         * Route name: Weekly Report Entry Routes
         * API Route: Weekly Report Entry API Portal
         * Roles Allowed: Admin, Student, Coordinator
         */
        require base_path('routes/weekly-entries.php');

        /**
         * Route name: Summary Routes
         * API Route: Summary API Portal
         * Roles Allowed: Chairperson, Coordinator
         */
        require base_path('routes/summary.php');

        /**
         * Route name: Offices Routes
         * API Route: Offices API Portal
         * Roles Allowed: TBA
         */
        require base_path('routes/offices.php');

        /**
         * Route name: Work Posts Routes
         * API Route: Work Posts API Portal
         * Roles Allowed: TBA
         */
        require base_path('routes/work-posts.php');

        /**
         * Route name: Applicants Routes
         * API Route: Applicants API Portal
         * Roles Allowed: TBA
         */
        require base_path('routes/applicants.php');

        /**
         * Route name: Document Submission Routes
         * API Route: Document Submission API Portal
         * Roles Allowed: TBA
         */
        require base_path('routes/document-submissions.php');

        /**
         * Route name: Homes Routes
         * API Route: Homes API Portal
         * Roles Allowed: TBA
         */
        require base_path('routes/homes.php');
        
        /**
         * Route name: Endorsements Routes
         * API Route: Endorsements API Portal
         * Roles Allowed: Student
         */
        require base_path('routes/endorsements.php');


        /**
         * Log Routes Here
         */
        Route::prefix('/logs')->group(function () {
            Route::get('/', [LogController::class, 'getAllLogs']);
        });

        /**
         * Messaging Routes Here
         */
        Route::prefix('/messaging')->group(function () {

            Route::get('/search-users-and-groups', [MessagingController::class, 'searchUsersAndGroups']);
            Route::get('/search-users', [MessagingController::class, 'searchUsers']);

            /**
             * Group Routes
             */

            Route::get('/my-groups', [MessagingController::class, 'getMyGroups']); // Get all your created group
            Route::get('/groups', [MessagingController::class, 'getGroups']);
            Route::post('/groups', [MessagingController::class, 'createGroup']);
            Route::get('/groups/{group_id}', [MessagingController::class, 'getGroupDetails']);
            Route::get('/groups/{group_id}/messages', [MessagingController::class, 'getGroupMessages']);
        });


        /**
         * TESTING ROUTES HERE
         */
        Route::prefix('/testing')->group(function () {

            /**
             * TESTING
             */
            Route::get('/', [TestController::class, 'test']);
        });

        // User-Roles Routes
        // resources: /user-roles
        Route::get('/user-roles', [UserRoleController::class, 'getUserRoles']);

        // WorkType Routes
        // resources: /work-types
        Route::get('/work-types', [WorkTypeController::class, 'getAllWorkTypes']);


        // Admin Routes
        require base_path('routes/admin.php');
        // Dean Routes
        require base_path('routes/dean.php');
        // Company Routes
        require base_path('routes/company.php');
        // Chairperson Routes
        require base_path('routes/chairperson.php');
        // Student Routes
        require base_path('routes/student.php');
        // Supervisor Routes
        require base_path('routes/supervisor.php');
        // OSA Routes
        require base_path('routes/osa.php');
        // Coordinator Routes
        require base_path('routes/coordinator.php');
        // Summary Routes
        require base_path('routes/summary.php');

        // OpenAI Summarize / Weekly Tasks endpoint (supports POST and GET)
        Route::match(['POST','GET'],'/summary/openai-summarize',[CoordinatorOpenAISummaryController::class,'summarize']);
    });
});
