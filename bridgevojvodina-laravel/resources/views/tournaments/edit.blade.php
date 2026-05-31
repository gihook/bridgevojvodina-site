<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Tournament') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ uploadModalOpen: false, uploadRoundId: '', uploadRoundName: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('tournaments.update', $tournament) }}">
                        @csrf
                        @method('PATCH')
                        @include('tournaments.form')
                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Update Tournament') }}
                            </x-primary-button>
                        </div>
                    </form>

                    @if($tournament->team_results)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-bold mb-4">{{ __('Tournament Settings') }}</h3>
                            <form method="POST" action="{{ route('tournaments.bye-vp.update', $tournament) }}">
                                @csrf
                                @method('PATCH')
                                <div class="flex gap-4 items-end">
                                    <div class="w-48">
                                        <x-input-label for="bye_vp" :value="__('Bye VP')" />
                                        <x-text-input id="bye_vp" name="bye_vp" type="number" step="0.5" class="mt-1 block w-full" :value="old('bye_vp', $tournament->team_results->bye_vp ?? 12.0)" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('bye_vp')" />
                                    </div>
                                    <x-secondary-button type="submit">
                                        {{ __('Update Settings') }}
                                    </x-secondary-button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    {{ __('Victory Points awarded to a team when they have a "bye" round.') }}
                                </p>
                            </form>
                        </div>
                    @endif

                    @if($tournament->team_results && count($tournament->team_results->teams) >= 2)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-lg font-bold">{{ __('Rounds & Board Sets') }}</h3>
                                    @if(collect($tournament->team_results->rounds)->contains('status', 'idle'))
                                        <form method="POST" action="{{ route('tournaments.rounds.idle.destroy', $tournament) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete all idle rounds?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-900 font-bold uppercase tracking-wider">
                                                {{ __('Delete All Idle') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                
                                <div class="flex flex-col gap-4">
                                    <form method="POST" action="{{ route('tournaments.rounds.generate', $tournament) }}" onsubmit="return confirm('{{ __('New rounds will be appended to the current schedule. Are you sure?') }}')">
                                        @csrf
                                        <div class="flex gap-2 items-center">
                                            <select name="format" class="text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                <option value="">{{ __('Select Format') }}</option>
                                                <option value="single_round_robin">{{ __('Single Round Robin') }}</option>
                                                <option value="double_round_robin">{{ __('Double Round Robin') }}</option>
                                            </select>
                                            <x-secondary-button type="submit" class="!py-1 !text-[10px]">
                                                {{ count($tournament->team_results->rounds) > 0 ? __('Add More Rounds') : __('Generate Rounds') }}
                                            </x-secondary-button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('tournaments.rounds.upload-csv', $tournament) }}" enctype="multipart/form-data" onsubmit="return confirm('{{ __('New rounds will be appended from the CSV. Are you sure?') }}')">
                                        @csrf
                                        <div class="flex gap-2 items-center">
                                            <div class="flex-1">
                                                <input type="file" name="csv_file" accept=".csv" class="block w-full text-[10px] text-gray-500 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                                            </div>
                                            <x-secondary-button type="submit" class="!py-1 !text-[10px]">
                                                {{ __('Upload CSV') }}
                                            </x-secondary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            @if(count($tournament->team_results->rounds) > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Round') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Board Set') }}</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($tournament->team_results->rounds as $round)
                                                <tr class="align-top">
                                                    <td class="px-6 py-4">
                                                        <div class="text-sm font-bold text-gray-900 mb-2">{{ $round->name }}</div>
                                                        <!-- Match Pairs -->
                                                        <div class="space-y-1">
                                                            @foreach($round->matches as $match)
                                                                <div class="text-[11px] text-gray-500 flex items-center gap-2">
                                                                    <span class="font-medium text-gray-700">{{ collect($tournament->team_results->teams)->firstWhere('id', $match->home_team_id)->name ?? __('bye') }}</span>
                                                                    <span class="text-gray-300">vs</span>
                                                                    <span class="font-medium text-gray-700">{{ collect($tournament->team_results->teams)->firstWhere('id', $match->away_team_id)->name ?? __('bye') }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        @php
                                                            $statusColors = [
                                                                'idle' => 'bg-gray-100 text-gray-800',
                                                                'inProgress' => 'bg-blue-100 text-blue-800',
                                                                'complete' => 'bg-green-100 text-green-800',
                                                            ];
                                                            $roundStatus = $round->status ?? 'idle';
                                                        @endphp
                                                        <div class="flex flex-col gap-2">
                                                            <span class="inline-flex w-fit items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $statusColors[$roundStatus] ?? 'bg-gray-100 text-gray-800' }}">
                                                                {{ __($roundStatus) }}
                                                            </span>
                                                            <form method="POST" action="{{ route('tournaments.rounds.status.update', [$tournament, $round->id]) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <select name="status" onchange="this.form.submit()" class="text-[10px] py-1 px-2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                                    <option value="idle" {{ $roundStatus === 'idle' ? 'selected' : '' }}>{{ __('idle') }}</option>
                                                                    <option value="inProgress" {{ $roundStatus === 'inProgress' ? 'selected' : '' }}>{{ __('inProgress') }}</option>
                                                                    <option value="complete" {{ $roundStatus === 'complete' ? 'selected' : '' }}>{{ __('complete') }}</option>
                                                                </select>
                                                            </form>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        @if($round->board_set_id)
                                                            @php
                                                                $set = $boardSets->firstWhere('id', $round->board_set_id);
                                                            @endphp
                                                            @if($set)
                                                                <a href="{{ route('tournaments.board-sets.show', [$tournament, $set]) }}" class="group flex flex-col">
                                                                    <span class="font-bold text-indigo-600 group-hover:text-indigo-900 group-hover:underline">{{ $set->name }}</span>
                                                                    <span class="text-[10px] text-gray-400">{{ $set->created_at->format('d.m.Y H:i') }}</span>
                                                                </a>
                                                            @else
                                                                <span class="text-red-400 italic text-xs">{{ __('Set not found') }}</span>
                                                            @endif
                                                        @else
                                                            <button 
                                                                type="button" 
                                                                @click="uploadModalOpen = true; uploadRoundId = '{{ $round->id }}'; uploadRoundName = '{{ $round->name }}'"
                                                                class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-[10px] text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                            >
                                                                {{ __('Upload Boards') }}
                                                            </button>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div class="flex flex-col items-end gap-2">
                                                            @if(($round->status ?? 'idle') === 'idle')
                                                                <div class="flex gap-2">
                                                                    @if(!$loop->first)
                                                                        <form method="POST" action="{{ route('tournaments.rounds.reorder', [$tournament, $round->id]) }}">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <input type="hidden" name="direction" value="up">
                                                                            <button type="submit" class="text-gray-400 hover:text-indigo-600" title="{{ __('Move Up') }}">
                                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                                            </button>
                                                                        </form>
                                                                    @endif

                                                                    @if(!$loop->last)
                                                                        <form method="POST" action="{{ route('tournaments.rounds.reorder', [$tournament, $round->id]) }}">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <input type="hidden" name="direction" value="down">
                                                                            <button type="submit" class="text-gray-400 hover:text-indigo-600" title="{{ __('Move Down') }}">
                                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>

                                                                <form method="POST" action="{{ route('tournaments.rounds.destroy', [$tournament, $round->id]) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-[10px] uppercase font-bold tracking-wider">
                                                                        {{ __('Delete Round') }}
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-12 text-center text-gray-500 italic">
                                    {{ __('No rounds generated yet. Select a format and click Generate Rounds.') }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($tournament->team_results && count($tournament->team_results->teams) > 0)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold">{{ __('Teams') }}</h3>
                                <a href="{{ route('tournaments.teams.numbers.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('Manage Numbers') }}
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach(collect($tournament->team_results->teams)->sortBy(fn($t) => $t->number ?? 999999) as $team)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">{{ $team->number ?? '-' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $team->name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('tournaments.teams.edit', [$tournament, $team->id]) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upload Board Set Modal -->
        <div 
            x-show="uploadModalOpen" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            x-cloak
            @keydown.escape.window="uploadModalOpen = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="uploadModalOpen" 
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0" 
                    x-transition:enter-end="opacity-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100" 
                    x-transition:leave-end="opacity-0" 
                    class="fixed inset-0 transition-opacity" 
                    aria-hidden="true"
                    @click="uploadModalOpen = false"
                >
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div 
                    x-show="uploadModalOpen" 
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            {{ __('Upload Board Set for') }} <span x-text="uploadRoundName"></span>
                        </h3>
                        
                        <form method="POST" action="{{ route('tournaments.board-sets.upload', $tournament) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="round_id" :value="uploadRoundId">
                            
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="board_set_file_modal" :value="__('Board Set PBN File')" />
                                    <input type="file" id="board_set_file_modal" name="board_set_file" accept=".pbn" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <x-input-error class="mt-2" :messages="$errors->get('board_set_file')" />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button type="button" @click="uploadModalOpen = false">
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                                <x-primary-button type="submit">
                                    {{ __('Upload') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
