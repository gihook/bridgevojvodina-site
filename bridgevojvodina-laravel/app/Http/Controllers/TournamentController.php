<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentHydrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentHydrationService $hydrationService
    ) {}

    public function index(): View
    {
        $tournaments = Tournament::latest()->paginate(10);
        return view('tournaments.index', compact('tournaments'));
    }

    public function show(string $id): View
    {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->team_results) {
            $this->hydrationService->hydratePlayers($tournament->team_results);
        }

        return view('tournaments.show', compact('tournament'));
    }
}
