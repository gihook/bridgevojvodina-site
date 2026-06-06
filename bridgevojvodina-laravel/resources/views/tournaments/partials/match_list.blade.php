@foreach ($results->rounds as $round)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">{{ $round->name }}</h3>
                @if ($round->board_set_id)
                    @php
                        $boardSet = $tournament->boardSets->firstWhere('id', $round->board_set_id);
                    @endphp
                    @if ($boardSet)
                        <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full font-semibold">
                            {{ $boardSet->boards_count }} {{ __('Boards') }}
                        </span>
                    @endif
                @endif
            </div>
            <div class="space-y-4">
                @foreach ($round->matches as $match)
                    @php
                        $isBye = empty($match->home_team_id) || empty($match->away_team_id) || $match->home_team_id === 'bye' || $match->away_team_id === 'bye';
                        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
                        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);
                    @endphp
                    <div class="border rounded-lg bg-gray-50 overflow-hidden">
                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <div class="flex-1 text-center font-bold text-lg">
                                    @if($homeTeam)
                                        <a href="{{ route('tournaments.teams.show', ['tournament' => $tournament, 'team' => $homeTeam->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $homeTeam->name }}
                                        </a>
                                    @else
                                        {{ __('BYE') }}
                                    @endif
                                </div>
                                <div class="px-4 py-2 {{ $isBye ? 'bg-gray-400' : 'bg-blue-600' }} text-white rounded font-mono text-xl flex flex-col items-center">
                                    @if($isBye)
                                        <span class="text-xs uppercase font-black tracking-widest">{{ __('Bye') }}</span>
                                    @else
                                        <span>{{ $match->home_imp }} : {{ $match->away_imp }}</span>
                                    @endif
                                    <span class="text-xs">
                                        ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }})
                                    </span>
                                </div>
                                <div class="flex-1 text-center font-bold text-lg">
                                    @if($awayTeam)
                                        <a href="{{ route('tournaments.teams.show', ['tournament' => $tournament, 'team' => $awayTeam->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $awayTeam->name }}
                                        </a>
                                    @else
                                        {{ __('BYE') }}
                                    @endif
                                </div>
                            </div>
                            @if (!$isBye)
                                <div class="flex flex-col items-center gap-2 mt-3">
                                    <a href="{{ route('tournaments.match', ['tournament' => $tournament, 'round' => $round->id, 'match' => ($match->id ?: $match->home_team_id)]) }}" class="text-sm text-blue-600 font-semibold uppercase tracking-wider hover:text-blue-800 transition-colors">
                                        {{ __('View Details') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
