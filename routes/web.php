<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/items', function () {
        return view('items.index');
    })->name('items.index');

    Route::get('/stock-in', function () {
        return view('stock-in.index');
    })->name('stock-in.index');

    Route::get('/sales', function () {
        return view('sales.index');
    })->name('sales.index');

    Route::get('/stock-balance', function () {
        return view('stock-balance.index');
    })->name('stock-balance.index');
});

require __DIR__.'/auth.php';
