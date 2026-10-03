<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SampleImageController;
use App\Livewire\Attendance;
use App\Livewire\Auth\Login;
use App\Livewire\Batches;
use App\Livewire\Dashboard;
use App\Livewire\Fees;
use App\Livewire\More;
use App\Livewire\Reports;
use App\Livewire\Settings;
use App\Livewire\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/students', Students\Index::class)->name('students.index');
    Route::get('/students/new', Students\Form::class)->name('students.create');
    Route::get('/students/{student}', Students\Show::class)->whereNumber('student')->name('students.show');
    Route::get('/students/{student}/edit', Students\Form::class)->name('students.edit');
    Route::get('/samples/{sample}/image', SampleImageController::class)->name('samples.image');

    Route::get('/batches', Batches\Index::class)->name('batches.index');
    Route::get('/batches/new', Batches\Form::class)->name('batches.create');
    Route::get('/batches/{batch}/edit', Batches\Form::class)->name('batches.edit');

    Route::get('/attendance/{batch}/{date?}', Attendance\Take::class)
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendance.take');
    Route::get('/fees', Fees\Index::class)->name('fees.index');
    Route::get('/payments/{payment}/receipt', ReceiptController::class)->name('payments.receipt');

    Route::get('/more', More::class)->name('more');
    Route::get('/reports', Reports\Index::class)->name('reports.index');
    Route::get('/exports/{type}', ExportController::class)->name('exports');
    Route::get('/settings/class', Settings\School::class)->name('settings.school');
    Route::get('/settings/curriculum', Settings\Curriculum::class)->name('settings.curriculum');
    Route::get('/settings/fee-plans', Settings\FeePlans::class)->name('settings.fee-plans');
    Route::get('/settings/account', Settings\Account::class)->name('settings.account');
    Route::get('/settings/backups', Settings\Backups::class)->name('settings.backups');
    Route::get('/settings/backups/{file}', BackupDownloadController::class)->name('backups.download');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
