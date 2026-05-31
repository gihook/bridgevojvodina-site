<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

use App\Http\Controllers\ClubController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\EventController;

use App\Http\Controllers\UserController;

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TournamentController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('contact', [ContactController::class, 'index'])->name('contact');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'sr'])) {
        Session::put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

Route::resource('clubs', ClubController::class);
Route::resource('players', PlayerController::class);
Route::resource('events', EventController::class);
Route::resource('tournaments', TournamentController::class);
Route::get('tournaments/{tournament}/round/{round}/match/{home_team}', [TournamentController::class, 'match'])->name('tournaments.match');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', UserController::class);
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

Route::get('/run-migrations-v1-769a3d1f', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return '<pre>' . Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

