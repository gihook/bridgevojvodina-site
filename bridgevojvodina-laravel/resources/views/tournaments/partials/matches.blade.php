@foreach ($results->rounds as $round)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-bold mb-4">{{ $round->name }}</h3>
            <div class="space-y-6">
                @foreach ($round->matches as $match)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex-1 text-center font-bold text-lg">
                                {{ collect($results->teams)->firstWhere('id', $match->home_team_id)->name ?? 'Unknown' }}
                            </div>
                            <div class="px-4 py-2 bg-blue-600 text-white rounded font-mono text-xl">
                                {{ $match->home_imp }} : {{ $match->away_imp }}
                                <span class="text-sm block text-center">
                                    ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }})
                                </span>
                            </div>
                            <div class="flex-1 text-center font-bold text-lg">
                                {{ $match->away_team_id ? (collect($results->teams)->firstWhere('id', $match->away_team_id)->name ?? 'Unknown') : 'Slobodan (Bye)' }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <h4 class="font-semibold border-b mb-1">Postava Home:</h4>
                                <ul class="list-disc list-inside">
                                    @foreach ($match->home_lineup as $lp)
                                        <li>{{ $lp->player ? ($lp->player->first_name . ' ' . $lp->player->last_name) : 'Player ID: ' . $lp->player_id }} 
                                            <span class="text-gray-500">({{ $lp->butler_score > 0 ? '+' : '' }}{{ $lp->butler_score }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-semibold border-b mb-1">Postava Away:</h4>
                                <ul class="list-disc list-inside">
                                    @foreach ($match->away_lineup as $lp)
                                        <li>{{ $lp->player ? ($lp->player->first_name . ' ' . $lp->player->last_name) : 'Player ID: ' . $lp->player_id }}
                                            <span class="text-gray-500">({{ $lp->butler_score > 0 ? '+' : '' }}{{ $lp->butler_score }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
