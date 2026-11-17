<?php

declare(strict_types=1);

use App\Enums\UnitStatus;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\TenantPortalController;
use App\Models\SiteSetting;
use App\Models\Unit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', function (): Response {
    $unitModels = Unit::orderBy('unit_number')->get();
    $units = [];

    foreach ($unitModels as $unit) {
        if (! $unit instanceof Unit) {
            continue;
        }

        $statusValue = $unit->status instanceof UnitStatus
            ? $unit->status->value
            : (string) $unit->status;

        $unitsPhoto = $unit->photo_path
            ? Storage::url($unit->photo_path)
            : null;

        $units[] = [
            'id' => $unit->id,
            'unit_number' => $unit->unit_number,
            'monthly_price' => (int) $unit->monthly_price,
            'status' => $statusValue,
            'description' => $unit->description,
            'photo_url' => $unitsPhoto,
        ];
    }

    $setting = SiteSetting::current();

    return Inertia::render('Welcome', [
        'units' => $units,
        'adminPhone' => (string) $setting->whatsapp_number,
    ]);
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        Route::get('/leases', [LeaseController::class, 'index'])->name('leases.index');
        Route::post('/leases', [LeaseController::class, 'store'])->name('leases.store');
        Route::post('/leases/{lease}/terminate', [LeaseController::class, 'terminate'])->name('leases.terminate');
        Route::delete('/leases/{lease}', [LeaseController::class, 'destroy'])->name('leases.destroy');
        Route::get('/leases/{lease}/ktp', [LeaseController::class, 'showKtp'])->name('leases.ktp');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/generate', [PaymentController::class, 'generateMonthly'])->name('payments.generate');
        Route::post('/payments/{payment}/cash', [PaymentController::class, 'markCashPaid'])->name('payments.cash');
        Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'showProof'])->name('payments.proof');

        Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::put('/complaints/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');
        Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');
        Route::get('/complaints/{complaint}/photo', [ComplaintController::class, 'showPhoto'])->name('complaints.photo');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:tenant')->prefix('tenant')->name('tenant.')->group(function (): void {
        Route::get('/dashboard', [TenantPortalController::class, 'index'])->name('dashboard');
        Route::post('/payments/{payment}/proof', [TenantPortalController::class, 'uploadPaymentProof'])->name('payments.proof');
        Route::post('/complaints', [TenantPortalController::class, 'storeComplaint'])->name('complaints.store');
    });
});