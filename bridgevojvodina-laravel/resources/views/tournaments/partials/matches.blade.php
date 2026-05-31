@foreach ($results->rounds as $round)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-bold mb-4">{{ $round->name }}</h3>
            <div class="space-y-6">
                @foreach ($round->matches as $match)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div class="flex-1 text-center font-bold text-lg">
                                {{ collect($results->teams)->firstWhere('id', $match->home_team_id)->name ?? 'bye' }}
                            </div>
                            <div class="px-4 py-2 bg-blue-600 text-white rounded font-mono text-xl">
                                {{ $match->home_imp }} : {{ $match->away_imp }}
                                <span class="text-sm block text-center">
                                    ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }})
                                </span>
                            </div>
                            <div class="flex-1 text-center font-bold text-lg">
                                {{ collect($results->teams)->firstWhere('id', $match->away_team_id)->name ?? 'bye' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
