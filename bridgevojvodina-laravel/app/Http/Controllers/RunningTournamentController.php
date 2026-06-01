<?php

namespace App\Http\Controllers;

use App\Models\RunningTournament;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class RunningTournamentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $query = RunningTournament::latest();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $tournaments = $query->paginate(10);

        return view('tournaments.running_index', compact('tournaments'));
    }
}
