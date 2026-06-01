<?php

namespace App\Http\Controllers;

use App\Models\TournamentConfiguration;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class TournamentConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $query = TournamentConfiguration::latest();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $tournaments = $query->paginate(10);

        return view('tournaments.configurations_index', compact('tournaments'));
    }
}
