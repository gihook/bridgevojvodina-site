<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentHydrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;

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

    public function create(): View
    {
        Gate::authorize('create', Tournament::class);
        return view('tournaments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Tournament::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'details' => 'required|string',
            'is_completed' => 'boolean',
        ]);

        $validated['is_completed'] = $request->has('is_completed');

        $tournament = $request->user()->tournaments()->create($validated);

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament created successfully.'));
    }

    public function show(Tournament $tournament): View
    {
        if ($tournament->team_results) {
            $this->hydrationService->hydratePlayers($tournament->team_results);
        }

        return view('tournaments.show', compact('tournament'));
    }

    public function edit(Tournament $tournament): View
    {
        Gate::authorize('update', $tournament);
        return view('tournaments.edit', compact('tournament'));
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'details' => 'required|string',
            'is_completed' => 'boolean',
        ]);

        $validated['is_completed'] = $request->has('is_completed');

        $tournament->update($validated);

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament updated successfully.'));
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament deleted successfully.'));
    }
}
