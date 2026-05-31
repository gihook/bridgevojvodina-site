<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Enter Results') }}: {{ $homeTeam->name }} vs {{ $awayTeam->name }}
            </h2>
            <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tournaments.match.update', ['tournament' => $tournament, 'round' => $round->id, 'match' => ($match->id ?: $match->home_team_id)]) }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                    <!-- Open Room Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-blue-500">
                        <div class="text-center font-bold uppercase text-blue-800 mb-6 text-sm tracking-widest border-b pb-1 w-full">{{ __('Open Room') }}</div>
                        
                        <div class="flex flex-col items-center gap-4">
                            <!-- North (Home) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="N ({{ $homeTeam->name }})" />
                                <select name="open_n_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($homePlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->open_ns_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- East/West (Away) -->
                            <div class="flex justify-between w-full gap-4">
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="W ({{ $awayTeam->name }})" />
                                    <select name="open_w_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($awayPlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->open_ew_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="E ({{ $awayTeam->name }})" />
                                    <select name="open_e_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($awayPlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->open_ew_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- South (Home) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="S ({{ $homeTeam->name }})" />
                                <select name="open_s_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($homePlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->open_ns_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Closed Room Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-red-500">
                        <div class="text-center font-bold uppercase text-red-800 mb-6 text-sm tracking-widest border-b pb-1 w-full">{{ __('Closed Room') }}</div>
                        
                        <div class="flex flex-col items-center gap-4">
                            <!-- North (Away) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="N ({{ $awayTeam->name }})" />
                                <select name="closed_n_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($awayPlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->closed_ns_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- East/West (Home) -->
                            <div class="flex justify-between w-full gap-4">
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="W ({{ $homeTeam->name }})" />
                                    <select name="closed_w_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($homePlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->closed_ew_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="E ({{ $homeTeam->name }})" />
                                    <select name="closed_e_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($homePlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->closed_ew_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- South (Away) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="S ({{ $awayTeam->name }})" />
                                <select name="closed_s_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($awayPlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->closed_ns_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match Score -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{
                    homeImp: {{ $match->home_imp }},
                    awayImp: {{ $match->away_imp }},
                    homeVp: {{ $match->home_vp }},
                    awayVp: {{ $match->away_vp }},
                    boards: {{ $round->boards_per_round ?? $results->boards_per_round ?? 16 }},
                    
                    calculate() {
                        let imps = this.homeImp - this.awayImp;
                        let absImps = Math.abs(imps);
                        let x = 15 * Math.sqrt(this.boards);
                        
                        if (absImps >= x) {
                            this.homeVp = imps >= 0 ? 20.00 : 0.00;
                            this.awayVp = imps >= 0 ? 0.00 : 20.00;
                            return;
                        }
                        
                        if (absImps === 0) {
                            this.homeVp = 10.00;
                            this.awayVp = 10.00;
                            return;
                        }

                        let tau = (Math.sqrt(5) - 1) / 2;
                        let r = Math.pow(tau, 3);
                        let raw = 10 + 10 * ((1 - Math.pow(r, absImps / x)) / (1 - r));
                        
                        // WBF Rounding: truncate to 3, round to 2
                        let winVp = Math.round(Math.floor(raw * 1000 + 1e-9) / 10) / 100;
                        let loseVp = Math.round((20 - winVp) * 100) / 100;

                        if (imps > 0) {
                            this.homeVp = winVp.toFixed(2);
                            this.awayVp = loseVp.toFixed(2);
                        } else {
                            this.homeVp = loseVp.toFixed(2);
                            this.awayVp = winVp.toFixed(2);
                        }
                    }
                }">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-6 border-b pb-2">{{ __('Match Score') }}</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_imp" :value="__('Home IMPs')" />
                                        <x-text-input id="home_imp" name="home_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold" x-model.number="homeImp" @input="calculate()" required />
                                    </div>
                                    <div class="text-3xl font-black pt-6">:</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_imp" :value="__('Away IMPs')" />
                                        <x-text-input id="away_imp" name="away_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold" x-model.number="awayImp" @input="calculate()" required />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_vp" :value="__('Home VP')" />
                                        <x-text-input id="home_vp" name="home_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-blue-700 font-black bg-gray-100" x-model="homeVp" readonly required />
                                    </div>
                                    <div class="text-2xl font-bold pt-6">-</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_vp" :value="__('Away VP')" />
                                        <x-text-input id="away_vp" name="away_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-blue-700 font-black bg-gray-100" x-model="awayVp" readonly required />
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 italic text-center">
                                    {{ __('Victory Points are automatically calculated based on the WBF Continuous Scale.') }}
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
