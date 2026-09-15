<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WalkInController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('walk-in', [WalkInController::class, 'index'])->name('walk-in');
Route::get('walk-in/client', [WalkInController::class, 'findClient'])->name('walk-in.client');
Route::post('walk-in', [WalkInController::class, 'store'])->name('walk-in.store');
Route::get('book-an-appointment', [AppointmentController::class, 'index'])->name('appointment');
