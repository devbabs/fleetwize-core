<?php

use App\Http\Controllers\Company\AlarmController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DriverController;
use App\Http\Controllers\Company\ExpenseController;
use App\Http\Controllers\Company\FuelController;
use App\Http\Controllers\Company\IssueController;
use App\Http\Controllers\Company\LiveTrackingController;
use App\Http\Controllers\Company\MaintenanceController;
use App\Http\Controllers\Company\ReportController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Dashboard (multi-tenant)
|--------------------------------------------------------------------------
|
| Served from {company_slug}.{tenant_domain}. The `company` middleware
| alias (App\Http\Middleware\EnsureCompanyTenant) cross-checks the
| subdomain against the authenticated user's own company so one tenant
| can never reach another's subdomain. The workshop portal shares this
| same subdomain under a /workshop prefix — see routes/company-workshop.php.
|
*/

Route::domain('{company_slug}.'.config('fleetwize.tenant_domain'))->group(function () {
    Route::middleware(['auth', 'company'])->name('company.')->group(function () {
        Route::redirect('/dashboard', '/overview');

        Route::get('/overview', [DashboardController::class, 'index'])->name('overview');

        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/template', [VehicleController::class, 'template'])->name('vehicles.template');
        Route::post('/vehicles/import', [VehicleController::class, 'import'])->name('vehicles.import');
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        Route::get('/live-tracking', [LiveTrackingController::class, 'index'])->name('live-tracking.index');
        Route::get('/live-tracking/positions', [LiveTrackingController::class, 'positions'])->name('live-tracking.positions');

        Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::patch('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');

        Route::get('/fuel', [FuelController::class, 'index'])->name('fuel.index');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');

        Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
        Route::patch('/alarms/{fault}/clear', [AlarmController::class, 'clear'])->name('alarms.clear');
        Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/vehicles.pdf', [ReportController::class, 'vehiclesPdf'])->name('reports.vehicles-pdf');
        Route::get('/reports/vehicles.csv', [ReportController::class, 'vehiclesCsv'])->name('reports.vehicles-csv');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    require __DIR__.'/company-workshop.php';
});
