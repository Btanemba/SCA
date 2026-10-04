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
    Route::get('logout-home', [\App\Http\Controllers\Admin\LogoutController::class, 'logout'])->name('backpack.logout.home');
    Route::get('security-attendance', [SecurityAttendanceController::class, 'index'])->name('security.attendance');
    Route::get('security-attendance/search', [SecurityAttendanceController::class, 'search'])->name('security.attendance.search');
    Route::post('security-attendance/drop-off', [SecurityAttendanceController::class, 'dropOff'])->name('security.attendance.drop-off');
    Route::post('security-attendance/pick-up', [SecurityAttendanceController::class, 'pickUp'])->name('security.attendance.pick-up');

    Route::get('my-account', 'PersonCrudController@myAccount')->name('person.my-account');

    Route::get('academy-settings', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'edit'])->name('academy.settings');
    Route::put('academy-settings', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'update'])->name('academy.settings.update');
    Route::get('academy-settings/assets/{asset}', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'asset'])->name('academy.settings.asset');
    Route::get('my-payslips', [\App\Http\Controllers\Admin\PayrollController::class, 'myPayslips'])->name('payroll.mine');
    Route::get('payslips/{entry}', [\App\Http\Controllers\Admin\PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::get('payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/{payroll}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('payroll.show');
    Route::delete('payroll/{payroll}', [\App\Http\Controllers\Admin\PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::get('payroll/{payroll}/entries/create', [\App\Http\Controllers\Admin\PayrollController::class, 'entryForm'])->name('payroll.entries.create');
    Route::get('payroll/{payroll}/entries/{entry}/edit', [\App\Http\Controllers\Admin\PayrollController::class, 'entryForm'])->name('payroll.entries.edit');
    Route::post('payroll/{payroll}/entries', [\App\Http\Controllers\Admin\PayrollController::class, 'saveEntry'])->name('payroll.entries.store');
    Route::put('payroll/{payroll}/entries/{entry}', [\App\Http\Controllers\Admin\PayrollController::class, 'saveEntry'])->name('payroll.entries.update');
    Route::delete('payroll/{payroll}/entries/{entry}', [\App\Http\Controllers\Admin\PayrollController::class, 'deleteEntry'])->name('payroll.entries.destroy');
    Route::get('payroll/{payroll}/bank-letter', [\App\Http\Controllers\Admin\PayrollController::class, 'bankDocument'])->name('payroll.bank');
    Route::post('payroll/{payroll}/email-bank', [\App\Http\Controllers\Admin\PayrollController::class, 'emailBank'])->middleware('throttle:3,1')->name('payroll.email');
    Route::post('payroll/{payroll}/actions/{action}', [\App\Http\Controllers\Admin\PayrollController::class, 'transition'])->name('payroll.action');

    Route::get('expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('expenses/create', [\App\Http\Controllers\Admin\ExpenseController::class, 'form'])->name('expenses.create');
    Route::post('expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'save'])->name('expenses.store');
    Route::get('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('expenses/{expense}/edit', [\App\Http\Controllers\Admin\ExpenseController::class, 'form'])->name('expenses.edit');
    Route::put('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'save'])->name('expenses.update');
    Route::delete('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('expenses/{expense}/actions/{action}', [\App\Http\Controllers\Admin\ExpenseController::class, 'transition'])->name('expenses.action');
    Route::post('expenses/{expense}/receipt', [\App\Http\Controllers\Admin\ExpenseController::class, 'uploadReceipt'])->name('expenses.receipt');
    Route::get('expenses/{expense}/attachments/{asset}', [\App\Http\Controllers\Admin\ExpenseController::class, 'attachment'])->name('expenses.attachment');
    Route::get('expenses/{expense}/bank-letter', [\App\Http\Controllers\Admin\ExpenseController::class, 'bankDocument'])->name('expenses.bank');

    Route::middleware([
        \App\Http\Middleware\BlockSecurityRole::class,
        \App\Http\Middleware\RestrictAccountantRole::class,
    ])->group(function () {
        Route::crud('sac-role', 'SacRoleCrudController');
        Route::get('person/students/search', 'PersonCrudController@searchStudents')->name('person.students.search');        Route::get('person/students/{student}/details', 'PersonCrudController@showStudentDetails')->name('person.students.details');
        Route::post('person/{id}/send-registration-invite', 'PersonCrudController@sendRegistrationInvite')
            ->middleware('throttle:6,1')
            ->name('person.registration.invite');
        Route::crud('person', 'PersonCrudController');
        Route::crud('management-member', 'ManagementMemberCrudController');
        Route::crud('job-opening', 'JobOpeningCrudController');
        Route::get('job-application/{id}/resume', 'JobApplicationCrudController@downloadResume')->name('job-application.resume');
        Route::crud('job-application', 'JobApplicationCrudController');
        Route::crud('attendance', 'AttendanceCrudController');
    });
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
