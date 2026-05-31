<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClubController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clubs = Club::paginate(100);
        return view('clubs.index', compact('clubs'));
    }

    public function create()
    {
        $this->authorize('create', Club::class);
        return view('clubs.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Club::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'representative' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'status' => 'required|in:Active,Inactive',
            'link' => 'nullable|url|max:255',
        ]);

        Club::create($validated);

        return redirect()->route('clubs.index')->with('success', 'Club created successfully.');
    }

    public function show(Club $club)
    {
        return view('clubs.show', compact('club'));
    }

    public function edit(Club $club)
    {
        $this->authorize('update', $club);
        return view('clubs.edit', compact('club'));
    }

    public function update(Request $request, Club $club)
    {
        $this->authorize('update', $club);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'representative' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'status' => 'required|in:Active,Inactive',
            'link' => 'nullable|url|max:255',
        ]);

        $club->update($validated);

        return redirect()->route('clubs.index')->with('success', 'Club updated successfully.');
    }

    public function destroy(Club $club)
    {
        $this->authorize('delete', $club);
        $club->delete();

        return redirect()->route('clubs.index')->with('success', 'Club deleted successfully.');
    }
}
