<?php

// Work Post

use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\WorkPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('/work-posts')->group(function () {

  /**
   * GET
   */
  /**
   * GET
   */
  Route::get('/v2', [WorkPostController::class, 'getAllWorkPostsV2'])->middleware('role:company');
  Route::get('/', [WorkPostController::class, 'getAllWorkPosts']);
  Route::get('/{work_post_id}/endorsement', [WorkPostController::class, 'getWorkPostEndorsement'])->middleware('role:student');
  Route::get('/{work_post_id}/details', [WorkPostController::class, 'getWorkPostDetails'])->middleware('role:admin,student');


  /**
   * POST
   */
  Route::post('/apply', [WorkPostController::class, 'apply'])->middleware('role:student');

  /**
   * GET (STUDENT ROUTE)
   */
  /* Route::get('/for-student', [WorkPostController::class, 'fetchWorkPostsForStudent'])->middleware('role:student'); */

  /**
   * GET (STUDENT ROUTE)
   */

  // All Jobs
  Route::get('/for-student/all-jobs', [JobPostingController::class, 'fetchWorkPostsForStudent'])->middleware('role:student');

  // Internships
  // Immersions

});
