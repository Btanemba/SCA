<?php

use App\Http\Controllers\CompleteRegistrationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::view('/admissions', 'admissions.overview')->name('admissions.overview');
Route::view('/admissions/fees', 'admissions.fees')->name('admissions.fees');

Route::get('/management', [ManagementController::class, 'index'])->name('management.index');

Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{jobOpening}', [JobController::class, 'show'])->name('jobs.show');
Route::post('/jobs/{jobOpening}/apply', [JobController::class, 'apply'])
    ->middleware('throttle:5,1')
    ->name('jobs.apply');

Route::get('/registration/complete/{token}', [CompleteRegistrationController::class, 'show'])
    ->name('registration.complete');
Route::post('/registration/complete', [CompleteRegistrationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('registration.complete.store');
