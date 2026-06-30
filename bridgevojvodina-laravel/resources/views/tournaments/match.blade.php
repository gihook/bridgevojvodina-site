@php
    $matchFinished = \App\Http\Controllers\TournamentController::matchResultsVisible($match);
    $isImpScoring = ($results->scoring_type ?? 'vp') === 'imp';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $round->name }}: {{ collect($results->teams)->firstWhere('id', $match->home_team_id)->name ?? __('bye') }} vs {{ collect($results->teams)->firstWhere('id', $match->away_team_id)->name ?? __('bye') }}
            </h2>
            <div class="flex items-center gap-4">
                @if($results->player_butlers && !empty($results->player_butlers))
                    <a href="{{ route('tournaments.butler', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Butler') }}
                    </a>
                @endif
                <a href="{{ route('tournaments.show', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Tournament') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex-1 text-center font-bold text-2xl">
                            {{ collect($results->teams)->firstWhere('id', $match->home_team_id)->name ?? __('bye') }}
                        </div>
                        <div class="flex flex-col items-center gap-4">
                            <div class="px-8 py-4 bg-blue-600 text-white rounded-lg font-mono text-4xl flex flex-col items-center shadow-lg">
                                @if($matchFinished)
                                    @if(!$isImpScoring && ($match->vp_override ?? false))
                                        <span>{{ number_format($match->home_vp, 2) }} : {{ number_format($match->away_vp, 2) }}</span>
                                        <span class="text-sm mt-1">{{ __('VP') }}</span>
                                    @else
                                        <span>{{ $match->home_imp }} : {{ $match->away_imp }}</span>
                                        @if($isImpScoring)
                                            <span class="text-sm mt-1">{{ __('IMP') }}</span>
                                        @else
                                            <span class="text-sm mt-1">
                                                ({{ number_format($match->home_vp, 2) }} - {{ number_format($match->away_vp, 2) }} VP)
                                            </span>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-base uppercase tracking-widest">{{ __('IMPs hidden') }}</span>
                                    <span class="text-xs mt-1">{{ __('Visible when match is finished') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 text-center font-bold text-2xl">
                            {{ collect($results->teams)->firstWhere('id', $match->away_team_id)->name ?? __('bye') }}
                        </div>
                    </div>

                    @if (!empty($match->boards))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                            <!-- Open Room Table -->
                            <div class="flex flex-col items-center">
                                <div class="text-center font-bold uppercase text-blue-800 mb-4 text-sm tracking-widest border-b pb-1 w-full">{{ __('Open Room') }}</div>
                                <div class="flex flex-col items-center gap-1 w-full max-w-[280px] border-2 border-gray-200 p-2 bg-gray-50 rounded-lg shadow-sm">
                                    <!-- Row 1 -->
                                    <div class="w-1/3 border bg-blue-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                        <span class="font-black text-blue-600 mr-2 text-[10px]">N</span>
                                        <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->open_ns[0] ?? null)->first_name }} {{ optional($match->open_ns[0] ?? null)->last_name }}">
                                            {{ optional($match->open_ns[0] ?? null)->last_name }}
                                        </span>
                                    </div>

                                    <!-- Row 2 -->
                                    <div class="flex flex-row justify-between items-stretch w-full gap-1">
                                        <div class="flex-1 border bg-red-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                            <span class="font-black text-red-600 mr-2 text-[10px]">W</span>
                                            <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->open_ew[1] ?? null)->first_name }} {{ optional($match->open_ew[1] ?? null)->last_name }}">
                                                {{ optional($match->open_ew[1] ?? null)->last_name }}
                                            </span>
                                        </div>
                                        <div class="w-10 h-10 shrink-0 self-center"></div>
                                        <div class="flex-1 border bg-red-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                            <span class="font-black text-red-600 mr-2 text-[10px]">E</span>
                                            <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->open_ew[0] ?? null)->first_name }} {{ optional($match->open_ew[0] ?? null)->last_name }}">
                                                {{ optional($match->open_ew[0] ?? null)->last_name }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Row 3 -->
                                    <div class="w-1/3 border bg-blue-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                        <span class="font-black text-blue-600 mr-2 text-[10px]">S</span>
                                        <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->open_ns[1] ?? null)->first_name }} {{ optional($match->open_ns[1] ?? null)->last_name }}">
                                            {{ optional($match->open_ns[1] ?? null)->last_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Closed Room Table -->
                            <div class="flex flex-col items-center">
                                <div class="text-center font-bold uppercase text-blue-800 mb-4 text-sm tracking-widest border-b pb-1 w-full">{{ __('Closed Room') }}</div>
                                <div class="flex flex-col items-center gap-1 w-full max-w-[280px] border-2 border-gray-200 p-2 bg-gray-50 rounded-lg shadow-sm">
                                    <!-- Row 1 -->
                                    <div class="w-1/3 border bg-red-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                        <span class="font-black text-red-600 mr-2 text-[10px]">N</span>
                                        <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->closed_ns[0] ?? null)->first_name }} {{ optional($match->closed_ns[0] ?? null)->last_name }}">
                                            {{ optional($match->closed_ns[0] ?? null)->last_name }}
                                        </span>
                                    </div>

                                    <!-- Row 2 -->
                                    <div class="flex flex-row justify-between items-stretch w-full gap-1">
                                        <div class="flex-1 border bg-blue-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                            <span class="font-black text-blue-600 mr-2 text-[10px]">W</span>
                                            <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->closed_ew[1] ?? null)->first_name }} {{ optional($match->closed_ew[1] ?? null)->last_name }}">
                                                {{ optional($match->closed_ew[1] ?? null)->last_name }}
                                            </span>
                                        </div>
                                        <div class="w-10 h-10 shrink-0 self-center"></div>
                                        <div class="flex-1 border bg-blue-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                            <span class="font-black text-blue-600 mr-2 text-[10px]">E</span>
                                            <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->closed_ew[0] ?? null)->first_name }} {{ optional($match->closed_ew[0] ?? null)->last_name }}">
                                                {{ optional($match->closed_ew[0] ?? null)->last_name }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Row 3 -->
                                    <div class="w-1/3 border bg-red-50 text-center rounded flex items-center justify-center p-1 leading-tight overflow-hidden">
                                        <span class="font-black text-red-600 mr-2 text-[10px]">S</span>
                                        <span class="font-bold text-[9px] sm:text-xs truncate" title="{{ optional($match->closed_ns[1] ?? null)->first_name }} {{ optional($match->closed_ns[1] ?? null)->last_name }}">
                                            {{ optional($match->closed_ns[1] ?? null)->last_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($matchFinished)
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-center border-collapse">
                                    <thead class="bg-gray-50 font-bold">
                                        <tr>
                                            <th class="py-3 border" rowspan="2">{{ __('Board') }}</th>
                                            <th class="py-3 border bg-blue-50" colspan="5">{{ __('Open Room') }}</th>
                                            <th class="py-3 border bg-red-50" colspan="5">{{ __('Closed Room') }}</th>
                                            <th class="py-3 border" colspan="2">{{ __('IMPs') }}</th>
                                        </tr>
                                        <tr>
                                            <th class="py-2 border bg-blue-50 text-xs">{{ __('Contr.') }}</th>
                                            <th class="py-2 border bg-blue-50 text-xs">{{ __('Decl.') }}</th>
                                            <th class="py-2 border bg-blue-50 text-xs">{{ __('Lead') }}</th>
                                            <th class="py-2 border bg-blue-50 text-xs">{{ __('Tr.') }}</th>
                                            <th class="py-2 border bg-blue-50 text-xs">{{ __('Score') }}</th>
                                            <th class="py-2 border bg-red-50 text-xs">{{ __('Contr.') }}</th>
                                            <th class="py-2 border bg-red-50 text-xs">{{ __('Decl.') }}</th>
                                            <th class="py-2 border bg-red-50 text-xs">{{ __('Lead') }}</th>
                                            <th class="py-2 border bg-red-50 text-xs">{{ __('Tr.') }}</th>
                                            <th class="py-2 border bg-red-50 text-xs">{{ __('Score') }}</th>
                                            <th class="py-2 border text-xs">{{ __('H') }}</th>
                                            <th class="py-2 border text-xs">{{ __('A') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($match->boards as $board)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="py-3 border font-bold bg-gray-50">
                                                    <a href="{{ route('tournaments.board', [$tournament, $round->id, $board->board_number]) }}" class="text-blue-600 hover:underline">
                                                        {{ $board->board_number }}
                                                    </a>
                                                </td>
                                                <td class="py-3 border"><x-bridge-contract :contract="$board->home_contract" /></td>
                                                <td class="py-3 border">{{ $board->home_declarer }}</td>
                                                <td class="py-3 border"><x-bridge-contract :contract="$board->home_lead" /></td>
                                                <td class="py-3 border">{{ $board->home_tricks }}</td>
                                                <td class="py-3 border font-mono">{{ $board->home_score }}</td>
                                                <td class="py-3 border"><x-bridge-contract :contract="$board->away_contract" /></td>
                                                <td class="py-3 border">{{ $board->away_declarer }}</td>
                                                <td class="py-3 border"><x-bridge-contract :contract="$board->away_lead" /></td>
                                                <td class="py-3 border">{{ $board->away_tricks }}</td>
                                                <td class="py-3 border font-mono">{{ $board->away_score }}</td>
                                                <td class="py-3 border font-bold text-green-700">{{ $board->home_imp ?: '' }}</td>
                                                <td class="py-3 border font-bold text-red-700">{{ $board->away_imp ?: '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-xl border border-blue-100 bg-blue-50 p-6 text-center">
                                <div class="text-xs font-black uppercase tracking-widest text-blue-700">{{ __('Board results hidden during match') }}</div>
                                <div class="text-sm text-blue-600 mt-1">{{ __('Contracts and scores will be visible when the match is finished.') }}</div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-12 text-gray-500 italic">
                            {{ __('No board results available for this match.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
