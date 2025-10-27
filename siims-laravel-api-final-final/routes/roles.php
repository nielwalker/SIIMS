<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('/roles')->middleware('role:admin')->group(function () {

  // GET
  Route::get('/user-roles', [UserRoleController::class, 'getAllUserRoles']);
  Route::get('/', [RoleController::class, 'getAllRoles']);
  

  // POST
  Route::post('/', [RoleController::class, 'addNewRole']);
});