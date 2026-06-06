<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 text-gray-900">
        <h3 class="text-lg font-bold mb-4">{{ __('Table') }}</h3>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Team') }}</th>
                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('VP') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $sortedTeams = collect($results->teams)->sortByDesc(fn($t) => [$t->total_vp, -($t->number ?? 999999)]);
                @endphp
                @foreach ($sortedTeams as $index => $team)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold">
                            <a href="{{ route('tournaments.teams.show', ['tournament' => $tournament, 'team' => $team->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $team->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">{{ number_format($team->total_vp, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
