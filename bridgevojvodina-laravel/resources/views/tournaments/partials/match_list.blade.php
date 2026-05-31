@foreach ($results->rounds as $round)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-bold mb-4">{{ $round->name }}</h3>
            <div class="space-y-4">
                @foreach ($round->matches as $match)
                    <div class="border rounded-lg bg-gray-50 overflow-hidden">
                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <div class="flex-1 text-center font-bold text-lg">
                                    {{ collect($results->teams)->firstWhere('id', $match->home_team_id)->name ?? __('bye') }}
                                </div>
                                <div class="px-4 py-2 bg-blue-600 text-white rounded font-mono text-xl flex flex-col items-center">
                                    <span>{{ $match->home_imp }} : {{ $match->away_imp }}</span>
                                    <span class="text-xs">
                                        ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }})
                                    </span>
                                </div>
                                <div class="flex-1 text-center font-bold text-lg">
                                    {{ collect($results->teams)->firstWhere('id', $match->away_team_id)->name ?? __('bye') }}
                                </div>
                            </div>
                            @if ($match->away_team_id)
                                <div class="text-center mt-3">
                                    <a href="{{ route('tournaments.match', [$tournament, $round->id, $match->home_team_id]) }}" class="text-sm text-blue-600 font-semibold uppercase tracking-wider hover:text-blue-800 transition-colors">
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
