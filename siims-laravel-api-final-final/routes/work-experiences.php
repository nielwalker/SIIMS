<?php



use App\Http\Controllers\WorkExperienceController;
use Illuminate\Support\Facades\Route;

// Work Experience Routes
// resources: /work-experiences
Route::prefix('/work-experiences')->middleware('role:admin,student')->group(function () {
  // GET
  Route::get('/', [WorkExperienceController::class, 'getAllWorkExperiences']);

  // POST
  Route::post('/', action: [WorkExperienceController::class, 'addWorkExperience'])->middleware('role:student');

  /**
   * PUT
   */
  Route::put('/{work_experience_id}', [WorkExperienceController::class, 'updateWorkExperience'])->middleware('role:student');

  /**
   * DELETE
   */
  Route::delete('/{work_experience_id}', [WorkExperienceController::class, 'deleteWorkExperience']);
});
