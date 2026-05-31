<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Tournament') }}
        </h2>
    </x-slot>

    <div class="py-12">
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

                    @if($tournament->team_results && count($tournament->team_results->rounds) > 0)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-bold mb-4">{{ __('Upload Board Set') }}</h3>
                            
                            <form method="POST" action="{{ route('tournaments.board-sets.upload', $tournament) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="round_id" :value="__('Select Round')" />
                                        <select id="round_id" name="round_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            @foreach($tournament->team_results->rounds as $round)
                                                <option value="{{ $round->id }}">{{ $round->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error class="mt-2" :messages="$errors->get('round_id')" />
                                    </div>
                                    
                                    <div>
                                        <x-input-label for="board_set_file" :value="__('Board Set PBN File')" />
                                        <input type="file" id="board_set_file" name="board_set_file" accept=".pbn" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <x-input-error class="mt-2" :messages="$errors->get('board_set_file')" />
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <x-secondary-button type="submit">
                                        {{ __('Upload Board Set') }}
                                    </x-secondary-button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($tournament->team_results && count($tournament->team_results->teams) >= 2)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold">{{ __('Rounds & Board Sets') }}</h3>
                                
                                <form method="POST" action="{{ route('tournaments.rounds.generate', $tournament) }}" onsubmit="return confirm('{{ __('Generating rounds will overwrite all existing rounds and matches. Are you sure?') }}')">
                                    @csrf
                                    <div class="flex gap-2 items-center">
                                        <select name="format" class="text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">{{ __('Select Format') }}</option>
                                            <option value="single_round_robin">{{ __('Single Round Robin') }}</option>
                                            <option value="double_round_robin">{{ __('Double Round Robin') }}</option>
                                        </select>
                                        <x-secondary-button type="submit" class="!py-1 !text-[10px]">
                                            {{ count($tournament->team_results->rounds) > 0 ? __('Regenerate Rounds') : __('Generate Rounds') }}
                                        </x-secondary-button>
                                    </div>
                                </form>
                            </div>
                            
                            @if(count($tournament->team_results->rounds) > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Round') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Board Set') }}</th>
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
                                                            <span class="text-gray-400 italic text-xs">{{ __('None') }}</span>
                                                        @endif
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

                    @php
                        $unassignedSets = $boardSets->filter(function($set) use ($tournament) {
                            return !$tournament->team_results || !collect($tournament->team_results->rounds)->contains('board_set_id', $set->id);
                        });
                    @endphp

                    @if($unassignedSets->count() > 0)
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-bold mb-4 text-gray-600">{{ __('Unassigned Board Sets') }}</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($unassignedSets as $set)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <a href="{{ route('tournaments.board-sets.show', [$tournament, $set]) }}" class="text-indigo-600 hover:text-indigo-900 font-bold">
                                                        {{ $set->name }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $set->created_at->format('d.m.Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($tournament->team_results && count($tournament->team_results->teams) > 0)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-bold mb-4">{{ __('Teams') }}</h3>
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
                                        @foreach($tournament->team_results->teams as $team)
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
    </div>
</x-app-layout>
