<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Enter Results') }}: {{ $homeTeam->name }} vs {{ $awayTeam->name }}
            </h2>
            <a href="{{ route('tournaments.match', [$tournament, $round->id, $homeTeam->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Match') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tournaments.match.update', [$tournament, $round->id, $homeTeam->id]) }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Home Team Lineup -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-blue-500">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 text-blue-800">{{ $homeTeam->name }} {{ __('Lineup') }}</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <x-input-label :value="__('Open Room NS (Select 2)')" />
                                    <div class="grid grid-cols-1 gap-2 mt-1">
                                        @foreach($homePlayers as $player)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="open_ns_ids[]" value="{{ $player->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ in_array($player->id, $match->open_ns_ids) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-600">{{ $player->last_name }} {{ $player->first_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100">
                                    <x-input-label :value="__('Closed Room EW (Select 2)')" />
                                    <div class="grid grid-cols-1 gap-2 mt-1">
                                        @foreach($homePlayers as $player)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="closed_ew_ids[]" value="{{ $player->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ in_array($player->id, $match->closed_ew_ids) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-600">{{ $player->last_name }} {{ $player->first_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Away Team Lineup -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-red-500">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 text-red-800">{{ $awayTeam->name }} {{ __('Lineup') }}</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <x-input-label :value="__('Open Room EW (Select 2)')" />
                                    <div class="grid grid-cols-1 gap-2 mt-1">
                                        @foreach($awayPlayers as $player)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="open_ew_ids[]" value="{{ $player->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ in_array($player->id, $match->open_ew_ids) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-600">{{ $player->last_name }} {{ $player->first_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100">
                                    <x-input-label :value="__('Closed Room NS (Select 2)')" />
                                    <div class="grid grid-cols-1 gap-2 mt-1">
                                        @foreach($awayPlayers as $player)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="closed_ns_ids[]" value="{{ $player->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ in_array($player->id, $match->closed_ns_ids) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-600">{{ $player->last_name }} {{ $player->first_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match Score -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-6 border-b pb-2">{{ __('Match Score') }}</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_imp" :value="__('Home IMPs')" />
                                        <x-text-input id="home_imp" name="home_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold" :value="old('home_imp', $match->home_imp)" required />
                                    </div>
                                    <div class="text-3xl font-black pt-6">:</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_imp" :value="__('Away IMPs')" />
                                        <x-text-input id="away_imp" name="away_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold" :value="old('away_imp', $match->away_imp)" required />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_vp" :value="__('Home VP')" />
                                        <x-text-input id="home_vp" name="home_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-blue-700 font-black" :value="old('home_vp', $match->home_vp)" required />
                                    </div>
                                    <div class="text-2xl font-bold pt-6">-</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_vp" :value="__('Away VP')" />
                                        <x-text-input id="away_vp" name="away_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-blue-700 font-black" :value="old('away_vp', $match->away_vp)" required />
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 italic text-center">
                                    {{ __('Enter Victory Points manually or based on WBF scale.') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <x-primary-button class="px-12 py-3 text-lg">
                                {{ __('Save Results') }}
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
