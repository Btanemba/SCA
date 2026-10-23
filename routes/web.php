<?php

use App\Http\Controllers\CompleteRegistrationController;
use App\Http\Controllers\AdmissionFeesController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'home'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');

Route::view('/admissions', 'admissions.overview')->name('admissions.overview');
Route::get('/admissions/registration-form', RegistrationFormController::class)->name('admissions.registration-form');
Route::get('/admissions/fees', [AdmissionFeesController::class, 'index'])->name('admissions.fees');
Route::get('/admissions/fees/brochure', [AdmissionFeesController::class, 'brochure'])->name('admissions.fees.brochure');

Route::get('/management', [ManagementController::class, 'index'])->name('management.index');
Route::get('/staffs', [StaffController::class, 'index'])->name('staffs.index');

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
