<?php

declare(strict_types=1);

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecurrenceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\MessageSettingController;
use App\Http\Controllers\Settings\RecurrenceSettingController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\WhatsappSettingController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\InitializeTenancyBySession;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    InitializeTenancyBySession::class,
    EnsurePasswordChanged::class,
    EnsureTenantAccess::class,
    EnsureModuleEnabled::class,
    EnsurePermission::class,
])->group(function (): void {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard.index');

    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'status'])->name('appointments.status');
    Route::post('appointments/{appointment}/notify', [AppointmentController::class, 'notify'])->name('appointments.notify');
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('search', [SearchController::class, 'index'])->name('search.index');

    Route::post('customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
    Route::put('customers/{customer}/quick', [CustomerController::class, 'quickUpdate'])->name('customers.quick-update');
    Route::resource('customers', CustomerController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('recurrences', RecurrenceController::class)->except(['show']);

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/sales/pdf', [ReportController::class, 'salesPdf'])->name('reports.sales-pdf');
    Route::get('reports/appointments/pdf', [ReportController::class, 'appointmentsPdf'])->name('reports.appointments-pdf');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/users/check-email', [UserController::class, 'checkEmail'])->name('users.check-email');
        Route::resource('users', UserController::class)->except(['show']);

        Route::resource('roles', RoleController::class)->except(['show']);

        Route::redirect('/messages', '/settings/messages/booking')->name('messages.index');
        Route::get('/messages/{type}', [MessageSettingController::class, 'show'])->name('messages.show')->whereIn('type', MessageSettingController::TYPES);
        Route::put('/messages/{type}', [MessageSettingController::class, 'update'])->name('messages.update')->whereIn('type', MessageSettingController::TYPES);

        Route::get('/recurrence', [RecurrenceSettingController::class, 'show'])->name('recurrence.index');
        Route::put('/recurrence', [RecurrenceSettingController::class, 'update'])->name('recurrence.update');

        Route::get('/whatsapp', [WhatsappSettingController::class, 'show'])->name('whatsapp.index');
        Route::get('/whatsapp/status', [WhatsappSettingController::class, 'status'])->name('whatsapp.status');
        Route::post('/whatsapp/restart', [WhatsappSettingController::class, 'restart'])->name('whatsapp.restart');
        Route::post('/whatsapp/test', [WhatsappSettingController::class, 'test'])->name('whatsapp.test');
    });
});
