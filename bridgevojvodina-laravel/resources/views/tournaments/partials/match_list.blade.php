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
                        $matchStatus = $match->status ?? 'pending';
                        $matchFinished = \App\Http\Controllers\TournamentController::matchResultsVisible($match);
                        $publicPlayerId = auth()->user()?->player_id;
                        $openAction = (!$isBye && $publicPlayerId) ? \App\Http\Controllers\PlayerScoringController::publicRoomAction((int) $publicPlayerId, $results, $round, $match, 'open') : null;
                        $closedAction = (!$isBye && $publicPlayerId) ? \App\Http\Controllers\PlayerScoringController::publicRoomAction((int) $publicPlayerId, $results, $round, $match, 'closed') : null;
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
                                    @elseif($matchFinished)
                                        <span>{{ $match->home_imp }} : {{ $match->away_imp }}</span>
                                    @elseif($matchStatus === 'inProgress')
                                        <span>0 : 0</span>
                                    @else
                                        <span class="text-xs uppercase font-black tracking-widest">{{ __('Not played yet') }}</span>
                                    @endif
                                    <span class="text-xs">
                                        @if($isBye || $matchFinished)
                                            ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }})
                                        @elseif($matchStatus === 'inProgress')
                                            {{ __('In progress') }}
                                        @else
                                            {{ __('Waiting to start') }}
                                        @endif
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
                                    @if($openAction || $closedAction)
                                        <div class="flex flex-wrap justify-center gap-2">
                                            @foreach(['open' => $openAction, 'closed' => $closedAction] as $roomKey => $action)
                                                @if($action)
                                                    @if($action['is_seated'])
                                                        <a href="{{ route('scoring.room.show', [$tournament->id, $round->id, ($match->id ?: $match->home_team_id), $roomKey]) }}" class="inline-flex items-center px-3 py-1 rounded-md bg-gray-800 text-white text-[10px] font-black uppercase tracking-widest hover:bg-gray-700">
                                                            {{ __($roomKey === 'open' ? 'Open Room' : 'Closed Room') }}
                                                        </a>
                                                    @else
                                                        <form method="POST" action="{{ route('scoring.match.sit', [$tournament->id, $round->id, ($match->id ?: $match->home_team_id)]) }}">
                                                            @csrf
                                                            <input type="hidden" name="room" value="{{ $roomKey }}">
                                                            <input type="hidden" name="enter_after_sit" value="1">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 text-[10px] font-black uppercase tracking-widest hover:bg-gray-100">
                                                                {{ __($roomKey === 'open' ? 'Open Room' : 'Closed Room') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
