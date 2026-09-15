<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AdminModuleController;
use App\Http\Controllers\Admin\FormTemplateController;
use App\Http\Controllers\Admin\RddRequestController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WalkInController;
use App\Http\Middleware\CaptureVisitorsMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->middleware(CaptureVisitorsMiddleware::class)
    ->name('home');
Route::get('walk-in', [WalkInController::class, 'index'])->name('walk-in');
Route::get('walk-in/client', [WalkInController::class, 'findClient'])->name('walk-in.client');
Route::post('walk-in/validate', [WalkInController::class, 'validateDetails'])->name('walk-in.validate');
Route::post('walk-in', [WalkInController::class, 'store'])->name('walk-in.store');
Route::get('book-an-appointment', [AppointmentController::class, 'index'])->name('appointment');
Route::get('book-an-appointment/client', [AppointmentController::class, 'findClient'])->name('appointment.client');
Route::post('book-an-appointment/validate-booking', [AppointmentController::class, 'validateBooking'])->name('appointment.validate-booking');
Route::post('book-an-appointment/validate', [AppointmentController::class, 'validateDetails'])->name('appointment.validate');
Route::post('book-an-appointment', [AppointmentController::class, 'store'])->name('appointment.store');

Route::get('dashboard', [AdminManagementController::class, 'dashboard'])
    ->middleware(['auth', 'role:superadmin'])
    ->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('login', [AdminAuthController::class, 'store'])->middleware('guest')->name('login.store');
    Route::post('logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'role:superadmin'])->group(function () {
        Route::get('/', fn () => to_route('dashboard'))->name('dashboard');
        Route::post('delete-challenge', [AdminManagementController::class, 'deleteChallenge'])->name('delete-challenge');
        Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::patch('requests/{serviceRequest}/proceed', [ServiceRequestController::class, 'proceed'])->name('requests.proceed');
        Route::get('requests/{serviceRequest}/rdd-request', [RddRequestController::class, 'create'])->name('requests.rdd.create');
        Route::post('requests/{serviceRequest}/rdd-request', [RddRequestController::class, 'store'])->name('requests.rdd.store');
        Route::get('requests/{serviceRequest}/rdd-request/pdf', [RddRequestController::class, 'downloadPdf'])->name('requests.rdd.pdf');
        Route::get('requests/{serviceRequest}/rdd-request/payment', [RddRequestController::class, 'editPayment'])->name('requests.rdd.payment.edit');
        Route::post('requests/{serviceRequest}/rdd-request/payment', [RddRequestController::class, 'updatePayment'])->name('requests.rdd.payment.update');
        Route::get('requests/{serviceRequest}/rdd-request/feedback', [RddRequestController::class, 'editFeedback'])->name('requests.rdd.feedback.edit');
        Route::post('requests/{serviceRequest}/rdd-request/feedback/remind', [RddRequestController::class, 'sendFeedbackReminder'])->name('requests.rdd.feedback.remind');
        Route::get('roles-and-permissions', [AdminManagementController::class, 'roles'])->name('roles');
        Route::get('roles-and-permissions/create', [AdminManagementController::class, 'createRole'])->name('roles.create');
        Route::get('roles-and-permissions/{role}/edit', [AdminManagementController::class, 'editRole'])->name('roles.edit');
        Route::post('roles-and-permissions', [AdminManagementController::class, 'saveRole'])->name('roles.store');
        Route::put('roles-and-permissions/{role}', [AdminManagementController::class, 'saveRole'])->name('roles.update');
        Route::delete('roles-and-permissions/{role}', [AdminManagementController::class, 'deleteRole'])->name('roles.destroy');
        Route::get('users', [AdminManagementController::class, 'users'])->name('users');
        Route::get('users/create', [AdminManagementController::class, 'createUser'])->name('users.create');
        Route::get('users/{user}/edit', [AdminManagementController::class, 'editUser'])->name('users.edit');
        Route::post('users', [AdminManagementController::class, 'saveUser'])->name('users.store');
        Route::put('users/{user}', [AdminManagementController::class, 'saveUser'])->name('users.update');
        Route::delete('users/{user}', [AdminManagementController::class, 'deleteUser'])->name('users.destroy');
        Route::get('form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
        Route::get('form-templates/{formTemplate}/edit', [FormTemplateController::class, 'edit'])->name('form-templates.edit');
        Route::put('form-templates/{formTemplate}', [FormTemplateController::class, 'update'])->name('form-templates.update');
        Route::get('smtp-configuration', [AdminManagementController::class, 'smtp'])->name('smtp');
        Route::put('smtp-configuration', [AdminManagementController::class, 'saveSmtp'])->name('smtp.update');
        Route::get('my-account', [AdminManagementController::class, 'account'])->name('account');
        Route::post('my-account', [AdminManagementController::class, 'saveAccount'])->name('account.update');
        Route::get('{module}', [AdminModuleController::class, 'show'])->name('modules.show');
    });
});
