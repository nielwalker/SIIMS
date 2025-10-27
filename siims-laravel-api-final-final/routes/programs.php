<?php


use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;


// Program Routes
// resources: /programs

Route::prefix('/programs')->middleware('role:admin,dean,chairperson')->group(function () {

  // GET
  Route::get('/dean', [ProgramController::class, 'getAllProgramsByDean']);
  Route::get('/lists', [ProgramController::class, 'getAllListsOfPrograms']);
  Route::get('/', [ProgramController::class, 'getAllPrograms']);

  // POST
  Route::post('/', [ProgramController::class, 'addNewProgram']);

  // PUT 
  Route::put('/{program_id}', [ProgramController::class, 'updateProgramById']);

  // DELETE
  Route::delete('/{program_id}', [ProgramController::class, 'deleteProgram'])->middleware('role:admin,dean');
});