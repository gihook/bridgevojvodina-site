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

                    @if(isset($boardSets) && $boardSets->count() > 0)
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-bold mb-4">{{ __('Board Sets') }}</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Round') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($boardSets as $set)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $set->name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @php
                                                        $associatedRounds = $tournament->team_results 
                                                            ? collect($tournament->team_results->rounds)->filter(fn($r) => $r->board_set_id == $set->id)->pluck('name')->implode(', ')
                                                            : '';
                                                    @endphp
                                                    {{ $associatedRounds ?: __('None') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $set->created_at->format('d.m.Y H:i') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div class="flex justify-end gap-3">
                                                        <a href="{{ route('tournaments.board-sets.show', [$tournament, $set]) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View Details') }}</a>
                                                        
                                                        <form method="POST" action="{{ route('tournaments.board-sets.destroy', [$tournament, $set]) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                                        </form>
                                                    </div>
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
