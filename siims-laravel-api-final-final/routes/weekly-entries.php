
<?php

use App\Http\Controllers\DailyRecordController;
use App\Http\Controllers\WeeklyEntryController;
use App\Http\Controllers\WeeklyRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('/weekly-entries')->middleware('role:admin,student,coordinator,chairperson,dean')->group(function () {

  // Test route without role restrictions
  Route::get('/test-auth', function() {
    $user = auth()->user();
    return response()->json([
      'authenticated' => !!$user,
      'user_id' => $user ? $user->id : null,
      'user_roles' => $user ? $user->roles->pluck('name')->toArray() : [],
      'user_email' => $user ? $user->email : null
    ]);
  });

  // POST
  Route::post('/', [WeeklyRecordController::class, 'create']);

  // GET
  Route::get('/student/{student_id}', [WeeklyRecordController::class, 'getByStudent'])->middleware('role:admin,chairperson,company,supervisor,coordinator');
  Route::get('/coordinator/{coordinator_id}', [WeeklyRecordController::class, 'getByCoordinator'])->middleware('role:admin,chairperson,coordinator,dean');
  Route::get('/', [WeeklyRecordController::class, 'get']);

  // PUT
  Route::put('/{week_id}', [WeeklyRecordController::class, 'update']);

  // DELETE
  Route::delete('/{week_id}', [WeeklyRecordController::class, 'delete']);
  
});
