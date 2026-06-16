<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PlayerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $players = Player::with('club')->paginate(100);
        return view('players.index', compact('players'));
    }

    public function create()
    {
        $this->authorize('create', Player::class);
        $clubs = Club::all();
        return view('players.create', compact('clubs'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Player::class);
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'club_id' => 'nullable|exists:clubs,id',
        ]);

        Player::create($validated);

        return redirect()->route('players.index')->with('success', 'Player created successfully.');
    }

    public function show(Player $player)
    {
        return view('players.show', compact('player'));
    }

    public function edit(Player $player)
    {
        $this->authorize('update', $player);
        $clubs = Club::all();
        return view('players.edit', compact('player', 'clubs'));
    }

    public function update(Request $request, Player $player)
    {
        $this->authorize('update', $player);
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'club_id' => 'nullable|exists:clubs,id',
        ]);

        $player->update($validated);

        return redirect()->route('players.index')->with('success', 'Player updated successfully.');
    }

    public function destroy(Player $player)
    {
        $this->authorize('delete', $player);
        $player->delete();

        return redirect()->route('players.index')->with('success', 'Player deleted successfully.');
    }
}
