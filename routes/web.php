<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\TenantSelectionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', EnsurePasswordChanged::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/tenants/select', [TenantSelectionController::class, 'index'])->name('tenant-selection.index');
    Route::post('/tenants/select', [TenantSelectionController::class, 'store'])->name('tenant-selection.store');
});

Route::post('/webhooks/whatsapp/{tenant}/{secret}', [WhatsappWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('whatsapp.webhook');

Route::get('/l/{code}.ics', [CalendarController::class, 'ics'])
    ->where('code', '[A-Za-z0-9]+')
    ->name('calendar.ics');

Route::get('/l/{code}', [CalendarController::class, 'show'])
    ->where('code', '[A-Za-z0-9]+')
    ->name('calendar.show');

Route::redirect('/', '/login');

require __DIR__.'/admin.php';
