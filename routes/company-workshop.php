<?php

use App\Http\Controllers\Workshop\DashboardController;
use App\Http\Controllers\Workshop\DiagnosticReportController;
use App\Http\Controllers\Workshop\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Workshop Portal
|--------------------------------------------------------------------------
|
| Nested under the company's subdomain (see routes/company.php), gated
| separately by the `company-workshop` middleware alias
| (App\Http\Middleware\EnsureWorkshopTenant) for the workshop-admin role.
|
*/

Route::prefix('workshop')->middleware(['auth', 'company-workshop'])->name('company-workshop.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

    Route::get('/reports', [DiagnosticReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [DiagnosticReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [DiagnosticReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [DiagnosticReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/pdf', [DiagnosticReportController::class, 'pdf'])->name('reports.pdf');
});
