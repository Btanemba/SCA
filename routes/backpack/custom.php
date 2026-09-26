<?php

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
    Route::crud('sac-role', 'SacRoleCrudController');
    Route::get('person/students/search', 'PersonCrudController@searchStudents')->name('person.students.search');
    Route::get('person/students/{student}/details', 'PersonCrudController@showStudentDetails')->name('person.students.details');
    Route::crud('person', 'PersonCrudController');
    Route::crud('job-opening', 'JobOpeningCrudController');
    Route::get('job-application/{id}/resume', 'JobApplicationCrudController@downloadResume')->name('job-application.resume');
    Route::crud('job-application', 'JobApplicationCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
