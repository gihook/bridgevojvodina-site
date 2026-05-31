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
Route::post('tournaments/{tournament}/board-sets', [TournamentController::class, 'uploadBoardSet'])->name('tournaments.board-sets.upload');
Route::get('tournaments/{tournament}/board-sets/{boardSet}', [TournamentController::class, 'showBoardSet'])->name('tournaments.board-sets.show');
Route::delete('tournaments/{tournament}/board-sets/{boardSet}', [TournamentController::class, 'destroyBoardSet'])->name('tournaments.board-sets.destroy');
Route::get('tournaments/{tournament}/teams/numbers', [TournamentController::class, 'editTeamNumbers'])->name('tournaments.teams.numbers.edit');
Route::patch('tournaments/{tournament}/teams/numbers', [TournamentController::class, 'updateTeamNumbers'])->name('tournaments.teams.numbers.update');
Route::get('tournaments/{tournament}/teams/{teamId}/edit', [TournamentController::class, 'editTeam'])->name('tournaments.teams.edit');
Route::patch('tournaments/{tournament}/teams/{teamId}', [TournamentController::class, 'updateTeam'])->name('tournaments.teams.update');
Route::patch('tournaments/{tournament}/rounds/{roundId}/status', [TournamentController::class, 'updateRoundStatus'])->name('tournaments.rounds.status.update');
Route::post('tournaments/{tournament}/rounds/generate', [TournamentController::class, 'generateRounds'])->name('tournaments.rounds.generate');
Route::delete('tournaments/{tournament}/rounds/idle', [TournamentController::class, 'destroyIdleRounds'])->name('tournaments.rounds.idle.destroy');
Route::delete('tournaments/{tournament}/rounds/{roundId}', [TournamentController::class, 'destroyRound'])->name('tournaments.rounds.destroy');
Route::post('tournaments/{tournament}/teams/{teamId}/players', [TournamentController::class, 'addPlayerToTeam'])->name('tournaments.teams.players.add');
Route::delete('tournaments/{tournament}/teams/{teamId}/players/{playerId}', [TournamentController::class, 'removePlayerFromTeam'])->name('tournaments.teams.players.remove');
Route::post('tournaments/{tournament}/teams/{teamId}/captain/{playerId}', [TournamentController::class, 'setTeamCaptain'])->name('tournaments.teams.captain.set');
Route::get('tournaments/{tournament}/round/{round}/match/{home_team}', [TournamentController::class, 'match'])->name('tournaments.match');
Route::get('tournaments/{tournament}/round/{round}/board/{board_number}', [TournamentController::class, 'board'])->name('tournaments.board');

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

