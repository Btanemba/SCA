<?php

use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::view('/admissions', 'admissions.overview')->name('admissions.overview');
Route::view('/admissions/fees', 'admissions.fees')->name('admissions.fees');

Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{jobOpening}', [JobController::class, 'show'])->name('jobs.show');
Route::post('/jobs/{jobOpening}/apply', [JobController::class, 'apply'])
    ->middleware('throttle:5,1')
    ->name('jobs.apply');
