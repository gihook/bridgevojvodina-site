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
                                        <x-input-label for="board_set_json" :value="__('Board Set JSON File')" />
                                        <input type="file" id="board_set_json" name="board_set_json" accept=".json" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <x-input-error class="mt-2" :messages="$errors->get('board_set_json')" />
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
