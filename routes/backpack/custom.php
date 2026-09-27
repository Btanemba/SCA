<?php

use App\Http\Controllers\SecurityAttendanceController;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('security-attendance', [SecurityAttendanceController::class, 'index'])->name('security.attendance');
    Route::get('security-attendance/search', [SecurityAttendanceController::class, 'search'])->name('security.attendance.search');
    Route::post('security-attendance/drop-off', [SecurityAttendanceController::class, 'dropOff'])->name('security.attendance.drop-off');
    Route::post('security-attendance/pick-up', [SecurityAttendanceController::class, 'pickUp'])->name('security.attendance.pick-up');

    Route::middleware(\App\Http\Middleware\BlockSecurityRole::class)->group(function () {
        Route::crud('sac-role', 'SacRoleCrudController');
        Route::get('person/students/search', 'PersonCrudController@searchStudents')->name('person.students.search');
        Route::get('person/students/{student}/details', 'PersonCrudController@showStudentDetails')->name('person.students.details');
        Route::crud('person', 'PersonCrudController');
        Route::crud('job-opening', 'JobOpeningCrudController');
        Route::get('job-application/{id}/resume', 'JobApplicationCrudController@downloadResume')->name('job-application.resume');
        Route::crud('job-application', 'JobApplicationCrudController');
        Route::crud('attendance', 'AttendanceCrudController');
    });
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
