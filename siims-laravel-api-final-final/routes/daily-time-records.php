
<?php

use App\Http\Controllers\DailyRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('/daily-time-records')->middleware('role:admin,student,coordinator')->group(function () {


  // POST
  Route::post('/', [DailyRecordController::class, 'create']);

  // GET  
  Route::get('/', [DailyRecordController::class, 'get']);
  
  // PUT
  Route::put('/{id}', [DailyRecordController::class, 'update']);

  // DELETE
  Route::delete('/{id}', [DailyRecordController::class, 'delete']);
  
});
