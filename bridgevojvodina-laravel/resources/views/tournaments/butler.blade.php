<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Butler (IMPs per Player)') }}
                </h2>
                <span class="text-gray-400 font-bold uppercase text-xs tracking-widest">{{ $tournament->title }}</span>
            </div>
            <a href="{{ route('tournaments.show', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($tournament->team_results && !empty($tournament->team_results->player_butlers))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-center">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider w-16">#</th>
                                        <th class="px-6 py-4 text-left text-xs font-black text-gray-500 uppercase tracking-wider">{{ __('Player') }}</th>
                                        <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider">{{ __('Boards') }}</th>
                                        <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider">{{ __('Total IMPs') }}</th>
                                        <th class="px-6 py-4 text-xs font-black text-indigo-600 uppercase tracking-wider font-bold">{{ __('IMPs/Board') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @php
                                        $sortedButlers = collect($tournament->team_results->player_butlers)->sortByDesc('imps_per_board');
                                    @endphp
                                    @foreach ($sortedButlers as $butler)
                                        @php
                                            $player = $butlerPlayers->get($butler->player_id);
                                        @endphp
                                        <tr class="hover:bg-indigo-50/30 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-300">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                                <div class="font-bold text-gray-900">
                                                    {{ $player ? ($player->first_name . ' ' . $player->last_name) : __('Unknown Player') }}
                                                </div>
                                                @if($player && $player->club)
                                                    <div class="text-[10px] text-gray-400 uppercase font-bold">{{ $player->club->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600">{{ $butler->boards_played }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm {{ $butler->total_imps > 0 ? 'text-green-600' : ($butler->total_imps < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                                {{ $butler->total_imps > 0 ? '+' : '' }}{{ number_format($butler->total_imps, 1) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap font-mono text-lg font-black text-indigo-700">
                                                {{ $butler->imps_per_board > 0 ? '+' : '' }}{{ number_format($butler->imps_per_board, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center text-gray-500 italic">
                        {{ __('No Butler scores available yet. Scores are calculated upon tournament publication.') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
