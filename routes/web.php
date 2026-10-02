<?php

use App\Livewire\Auth\Login;
use App\Livewire\Batches;
use App\Livewire\Dashboard;
use App\Livewire\Fees;
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
    Route::get('/students/{student}/edit', Students\Form::class)->name('students.edit');

    Route::get('/batches', Batches\Index::class)->name('batches.index');
    Route::get('/fees', Fees\Index::class)->name('fees.index');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
