<?php
namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TournamentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tournaments = Tournament::latest()->paginate(10);
        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Tournament::class);
        return view('tournaments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Tournament::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'details' => 'required|string',
        ]);

        Tournament::create([
            'title' => $request->title,
            'description' => $request->description,
            'details' => $request->details,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('tournaments.index')->with('success', 'Tournament created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament)
    {
        return view('tournaments.show', compact('tournament'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tournament $tournament)
    {
        $this->authorize('update', $tournament);
        return view('tournaments.edit', compact('tournament'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'details' => 'required|string',
        ]);

        $tournament->update($request->only('title', 'description', 'details'));

        return redirect()->route('tournaments.index')->with('success', 'Tournament updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tournament $tournament)
    {
        $this->authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('tournaments.index')->with('success', 'Tournament deleted successfully.');
    }
}
