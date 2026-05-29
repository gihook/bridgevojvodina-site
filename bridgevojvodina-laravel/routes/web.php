<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Session;

use App\Http\Controllers\ClubController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\EventController;

use App\Http\Controllers\UserController;

use App\Http\Controllers\ContactController;

use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('contact', [ContactController::class, 'index'])->name('contact');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'sr'])) {
        Session::put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

Route::resource('clubs', ClubController::class)->only(['index', 'show']);
Route::resource('players', PlayerController::class)->only(['index', 'show']);
Route::resource('events', EventController::class)->only(['index', 'show']);

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('clubs', ClubController::class)->except(['index', 'show']);
    Route::resource('players', PlayerController::class)->except(['index', 'show']);
    Route::resource('events', EventController::class)->except(['index', 'show']);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
