<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WalkInController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('walk-in', [WalkInController::class, 'index'])->name('walk-in');
Route::get('walk-in/client', [WalkInController::class, 'findClient'])->name('walk-in.client');
Route::post('walk-in/validate', [WalkInController::class, 'validateDetails'])->name('walk-in.validate');
Route::post('walk-in', [WalkInController::class, 'store'])->name('walk-in.store');
Route::get('book-an-appointment', [AppointmentController::class, 'index'])->name('appointment');
Route::get('book-an-appointment/client', [AppointmentController::class, 'findClient'])->name('appointment.client');
Route::post('book-an-appointment/validate-booking', [AppointmentController::class, 'validateBooking'])->name('appointment.validate-booking');
Route::post('book-an-appointment/validate', [AppointmentController::class, 'validateDetails'])->name('appointment.validate');
Route::post('book-an-appointment', [AppointmentController::class, 'store'])->name('appointment.store');
