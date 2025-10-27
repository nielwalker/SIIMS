

<?php

use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

Route::prefix('/certificates')->middleware('role:student')->group(function () {
  // POST
  Route::post('/', [CertificateController::class, 'addNewCertificate']);

  // GET
  Route::get('/', [CertificateController::class, 'getAllCertificates']);

  // DELETE
  Route::delete('/{certificate_id}', [CertificateController::class, 'deleteCertificateByID']);
});