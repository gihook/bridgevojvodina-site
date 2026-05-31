<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Club;
use App\Models\Player;
use App\Models\Event;
use App\Models\Tournament;

class HomeController extends Controller
{
    public function index()
    {
        $stats = [
            'clubs' => Club::count(),
            'players' => Player::count(),
            'events' => Event::count(),
            'tournaments' => Tournament::count(),
        ];

        return view('welcome', compact('stats'));
    }
}
