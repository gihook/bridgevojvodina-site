<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Board') }} {{ $boardNumber }} - {{ $round->name }}
                </h2>
                
                <div class="flex items-center bg-gray-100 rounded-lg p-1 ml-4 shadow-inner">
                    @if ($prevBoard)
                        <a href="{{ route('tournaments.board', [$tournament, $round->id, $prevBoard]) }}" class="px-3 py-1 bg-white rounded-md shadow-sm text-sm font-bold text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all" title="{{ __('Previous Board') }}">
                            &larr; {{ $prevBoard }}
                        </a>
                    @else
                        <span class="px-3 py-1 text-gray-300 text-sm font-bold cursor-not-allowed">
                            &larr;
                        </span>
                    @endif
                    
                    <span class="px-4 text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('Board') }}</span>
                    
                    @if ($nextBoard)
                        <a href="{{ route('tournaments.board', [$tournament, $round->id, $nextBoard]) }}" class="px-3 py-1 bg-white rounded-md shadow-sm text-sm font-bold text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all" title="{{ __('Next Board') }}">
                            {{ $nextBoard }} &rarr;
                        </a>
                    @else
                        <span class="px-3 py-1 text-gray-300 text-sm font-bold cursor-not-allowed">
                            &rarr;
                        </span>
                    @endif
                </div>
            </div>
            <a href="{{ route('tournaments.show', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col items-center justify-center gap-12 mb-12">
                        <!-- Top Row: North -->
                        @if ($boardData['physical_board'])
                            <div class="flex flex-col items-center gap-6">
                                <!-- North Hand -->
                                <div class="p-3 bg-blue-50 rounded-lg border border-blue-100 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-blue-400 mb-1 text-center">{{ __('North') }}</div>
                                    <x-bridge-hand :hand="$boardData['physical_board']->cards_north" />
                                </div>

                                <!-- Middle Row: West - Square - East -->
                                <div class="flex items-center gap-8 md:gap-16">
                                    <div class="p-3 bg-red-50 rounded-lg border border-red-100 shadow-sm">
                                        <div class="text-[10px] uppercase font-bold text-red-400 mb-1 text-center">{{ __('West') }}</div>
                                        <x-bridge-hand :hand="$boardData['physical_board']->cards_west" />
                                    </div>
                                    
                                    <!-- Board Info Center -->
                                    <div class="w-32 h-32 shrink-0 flex flex-col items-center justify-center bg-white rounded-lg shadow-sm border-2 border-gray-100 p-2">
                                        <div class="text-[10px] uppercase font-bold text-gray-400">{{ __('Board') }}</div>
                                        <div class="text-3xl font-black text-blue-900 mb-2 leading-none">{{ $boardNumber }}</div>
                                        
                                        <div class="w-full border-t pt-2 space-y-1">
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span class="font-bold text-gray-400 uppercase">{{ __('Dealer') }}</span>
                                                <span class="font-black text-blue-700">{{ $boardData['dealer'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span class="font-bold text-gray-400 uppercase">{{ __('Vuln') }}</span>
                                                <span class="font-black @if($boardData['vulnerability'] == 'All') text-red-600 @elseif($boardData['vulnerability'] == 'NS') text-green-700 @elseif($boardData['vulnerability'] == 'EW') text-orange-600 @else text-gray-600 @endif">
                                                    {{ __($boardData['vulnerability']) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-red-50 rounded-lg border border-red-100 shadow-sm">
                                        <div class="text-[10px] uppercase font-bold text-red-400 mb-1 text-center">{{ __('East') }}</div>
                                        <x-bridge-hand :hand="$boardData['physical_board']->cards_east" />
                                    </div>
                                </div>

                                <!-- South Hand -->
                                <div class="p-3 bg-blue-50 rounded-lg border border-blue-100 shadow-sm">
                                    <div class="text-[10px] uppercase font-bold text-blue-400 mb-1 text-center">{{ __('South') }}</div>
                                    <x-bridge-hand :hand="$boardData['physical_board']->cards_south" />
                                </div>
                            </div>
                        @else
                            <div class="text-gray-400 italic bg-gray-50 p-12 rounded-lg border-2 border-dashed border-gray-200">
                                {{ __('Card data not available for this board.') }}
                            </div>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-center border-collapse">
                            <thead class="bg-gray-50 font-bold text-gray-700">
                                <tr>
                                    <th class="py-3 border-x border-t px-2" rowspan="2">{{ __('NS Team') }}</th>
                                    <th class="py-3 border-x border-t px-2" rowspan="2">{{ __('EW Team') }}</th>
                                    <th class="py-3 border bg-blue-50 text-blue-800" colspan="5">{{ __('Open Room') }}</th>
                                    <th class="py-3 border bg-red-50 text-red-800" colspan="5">{{ __('Closed Room') }}</th>
                                    <th class="py-3 border" colspan="2">{{ __('IMPs') }}</th>
                                    <th class="py-3 border bg-green-50 text-green-800" colspan="2">{{ __('Butler') }}</th>
                                </tr>
                                <tr>
                                    <th class="py-2 border bg-blue-50 text-[10px] uppercase tracking-tighter">{{ __('Contr.') }}</th>
                                    <th class="py-2 border bg-blue-50 text-[10px] uppercase tracking-tighter">{{ __('Decl.') }}</th>
                                    <th class="py-2 border bg-blue-50 text-[10px] uppercase tracking-tighter">{{ __('Lead') }}</th>
                                    <th class="py-2 border bg-blue-50 text-[10px] uppercase tracking-tighter">{{ __('Tr.') }}</th>
                                    <th class="py-2 border bg-blue-50 text-[10px] uppercase tracking-tighter">{{ __('Score') }}</th>
                                    <th class="py-2 border bg-red-50 text-[10px] uppercase tracking-tighter">{{ __('Contr.') }}</th>
                                    <th class="py-2 border bg-red-50 text-[10px] uppercase tracking-tighter">{{ __('Decl.') }}</th>
                                    <th class="py-2 border bg-red-50 text-[10px] uppercase tracking-tighter">{{ __('Lead') }}</th>
                                    <th class="py-2 border bg-red-50 text-[10px] uppercase tracking-tighter">{{ __('Tr.') }}</th>
                                    <th class="py-2 border bg-red-50 text-[10px] uppercase tracking-tighter">{{ __('Score') }}</th>
                                    <th class="py-2 border text-[10px] uppercase tracking-tighter">{{ __('H') }}</th>
                                    <th class="py-2 border text-[10px] uppercase tracking-tighter">{{ __('A') }}</th>
                                    <th class="py-2 border bg-green-50 text-[10px] uppercase tracking-tighter">NS</th>
                                    <th class="py-2 border bg-green-50 text-[10px] uppercase tracking-tighter">EW</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($boardResults as $res)
                                    @php
                                        $openButler = $datum !== null && $res['board']->home_score !== null 
                                            ? app(\App\Services\TournamentHydrationService::class)->scoreToImp($res['board']->home_score - $datum) 
                                            : null;
                                        $closedButler = $datum !== null && $res['board']->away_score !== null 
                                            ? app(\App\Services\TournamentHydrationService::class)->scoreToImp($datum - $res['board']->away_score) 
                                            : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors border-b">
                                        <td class="py-3 border-x font-semibold px-2 text-left">{{ $res['home_team']->name ?? __('bye') }}</td>
                                        <td class="py-3 border-x font-semibold px-2 text-left">{{ $res['away_team']->name ?? __('bye') }}</td>
                                        
                                        <td class="py-3 border-x bg-blue-50/30"><x-bridge-contract :contract="$res['board']->home_contract" /></td>
                                        <td class="py-3 border-x bg-blue-50/30">{{ $res['board']->home_declarer }}</td>
                                        <td class="py-3 border-x bg-blue-50/30"><x-bridge-contract :contract="$res['board']->home_lead" /></td>
                                        <td class="py-3 border-x bg-blue-50/30">{{ $res['board']->home_tricks }}</td>
                                        <td class="py-3 border-x bg-blue-50/30 font-mono">{{ $res['board']->home_score }}</td>
                                        
                                        <td class="py-3 border-x bg-red-50/30"><x-bridge-contract :contract="$res['board']->away_contract" /></td>
                                        <td class="py-3 border-x bg-red-50/30">{{ $res['board']->away_declarer }}</td>
                                        <td class="py-3 border-x bg-red-50/30"><x-bridge-contract :contract="$res['board']->away_lead" /></td>
                                        <td class="py-3 border-x bg-red-50/30">{{ $res['board']->away_tricks }}</td>
                                        <td class="py-3 border-x bg-red-50/30 font-mono">{{ $res['board']->away_score }}</td>
                                        
                                        <td class="py-3 border-x font-bold text-green-700">{{ $res['board']->home_imp ?: '' }}</td>
                                        <td class="py-3 border-x font-bold text-red-700">{{ $res['board']->away_imp ?: '' }}</td>

                                        <td class="py-3 border-x bg-green-50/30 font-bold @if($openButler > 0) text-green-600 @elseif($openButler < 0) text-red-600 @endif">
                                            {{ $openButler > 0 ? '+' : '' }}{{ $openButler }}
                                        </td>
                                        <td class="py-3 border-x bg-green-50/30 font-bold @if($closedButler > 0) text-green-600 @elseif($closedButler < 0) text-red-600 @endif">
                                            {{ $closedButler > 0 ? '+' : '' }}{{ $closedButler }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if($datum !== null)
                                <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                    <tr>
                                        <td colspan="2" class="py-3 px-4 text-left uppercase tracking-widest text-gray-500 text-xs">{{ __('Datum') }}</td>
                                        <td colspan="10" class="py-3"></td>
                                        <td colspan="2" class="py-3 text-lg font-mono text-blue-900">{{ round($datum) }}</td>
                                        <td colspan="2" class="py-3"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
