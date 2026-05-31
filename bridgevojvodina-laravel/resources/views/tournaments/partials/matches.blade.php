@foreach ($results->rounds as $round)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-bold mb-4">{{ $round->name }}</h3>
            <div class="space-y-6">
                @foreach ($round->matches as $match)
                    <div class="border rounded-lg bg-gray-50 overflow-hidden" x-data="{ expanded: false }">
                        <div class="p-4 cursor-pointer hover:bg-gray-100 transition-colors" @click="expanded = !expanded">
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
                            @if (!empty($match->boards))
                                <div class="text-center mt-2">
                                    <span class="text-xs text-blue-600 font-semibold uppercase tracking-wider" x-text="expanded ? '{{ __('Hide Details') }}' : '{{ __('Show Details') }}'"></span>
                                </div>
                            @endif
                        </div>

                        @if (!empty($match->boards))
                            <div x-show="expanded" x-collapse>
                                <div class="border-t bg-white p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-8">
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
                                    <table class="min-w-full text-sm text-center">
                                        <thead class="bg-gray-50 font-bold">
                                            <tr>
                                                <th class="py-2 border" rowspan="2">{{ __('Board') }}</th>
                                                <th class="py-2 border" colspan="5">{{ __('Open Room') }}</th>
                                                <th class="py-2 border" colspan="5">{{ __('Closed Room') }}</th>
                                                <th class="py-2 border" colspan="2">{{ __('IMPs') }}</th>
                                            </tr>
                                            <tr>
                                                <th class="py-1 border text-xs">{{ __('Contr.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Decl.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Lead') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Tr.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Score') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Contr.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Decl.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Lead') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Tr.') }}</th>
                                                <th class="py-1 border text-xs">{{ __('Score') }}</th>
                                                <th class="py-1 border text-xs">{{ __('H') }}</th>
                                                <th class="py-1 border text-xs">{{ __('A') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($match->boards as $board)
                                                <tr class="hover:bg-blue-50">
                                                    <td class="py-2 border font-bold">{{ $board->board_number }}</td>
                                                    <td class="py-2 border"><x-bridge-contract :contract="$board->home_contract" /></td>
                                                    <td class="py-2 border">{{ $board->home_declarer }}</td>
                                                    <td class="py-2 border"><x-bridge-contract :contract="$board->home_lead" /></td>
                                                    <td class="py-2 border">{{ $board->home_tricks }}</td>
                                                    <td class="py-2 border font-mono">{{ $board->home_score }}</td>
                                                    <td class="py-2 border"><x-bridge-contract :contract="$board->away_contract" /></td>
                                                    <td class="py-2 border">{{ $board->away_declarer }}</td>
                                                    <td class="py-2 border"><x-bridge-contract :contract="$board->away_lead" /></td>
                                                    <td class="py-2 border">{{ $board->away_tricks }}</td>
                                                    <td class="py-2 border font-mono">{{ $board->away_score }}</td>
                                                    <td class="py-2 border font-bold text-green-700">{{ $board->home_imp ?: '' }}</td>
                                                    <td class="py-2 border font-bold text-red-700">{{ $board->away_imp ?: '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
