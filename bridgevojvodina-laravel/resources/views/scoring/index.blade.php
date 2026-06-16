<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Player Scoring Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        @if(isset($error))
            <div class="max-w-md mx-auto bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            {{ $error }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-md mx-auto bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded shadow-sm">
                <div class="text-sm font-semibold text-red-700">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        <div class="max-w-md mx-auto">
            @if(isset($players))
                <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-100">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-3">{{ __('Connect Player Profile') }}</h3>
                    <p class="text-xs text-gray-500 mb-4">{{ __('Choose the player name used in tournaments. This lets the site show matches where you are seated.') }}</p>

                    <form method="POST" action="{{ route('scoring.player.link') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="player_id" :value="__('Tournament Player Name')" />
                            <select id="player_id" name="player_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                                <option value="">{{ __('Select player') }}</option>
                                @foreach($players as $player)
                                    <option value="{{ $player->id }}">
                                        {{ $player->last_name }} {{ $player->first_name }}@if($player->club) - {{ $player->club->name }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('player_id')" />
                        </div>
                        <x-primary-button type="submit" class="w-full justify-center">
                            {{ __('Connect Player') }}
                        </x-primary-button>
                    </form>
                </div>
            @elseif(isset($linkedPlayer))
                <div class="bg-green-50 rounded-xl p-4 mb-6 border border-green-100 text-xs font-semibold text-green-800">
                    {{ __('Connected as') }} {{ $linkedPlayer->first_name }} {{ $linkedPlayer->last_name }}.
                </div>
            @endif

            <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('My Active Matches') }}</h3>
            
            @if(empty($matches))
                <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">{{ __('No active matches found for your player profile.') }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ __('Make sure your player profile is on one of the teams in a started match.') }}</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($matches as $m)
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow duration-200">
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-700">
                                        {{ __('Match Started') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                        {{ $m['round']->name }}
                                    </span>
                                </div>
                                <h4 class="text-md font-extrabold text-gray-800 leading-tight mb-1">{{ $m['tournament']->title }}</h4>
                                <div class="flex items-center text-sm font-medium text-gray-600 mt-3">
                                    <div class="flex-1 text-right pr-3">{{ $m['home_team']->name ?? '???' }}</div>
                                    <div class="px-2 text-gray-300 font-black">VS</div>
                                    <div class="flex-1 text-left pl-3">{{ $m['away_team']->name ?? '???' }}</div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 space-y-3">
                                @foreach($m['rooms'] as $room)
                                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="text-[10px] font-black uppercase tracking-widest {{ $room['key'] === 'open' ? 'text-blue-700' : 'text-red-700' }}">
                                                {{ $room['label'] }}
                                            </div>
                                            @if($room['is_seated'])
                                                <form method="POST" action="{{ route('scoring.match.leave', [$m['tournament']->id, $m['round']->id, ($m['match']->id ?: $m['match']->home_team_id)]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-600 hover:text-red-800">
                                                        {{ __('Leave') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($room['seats'] as $seat)
                                                <div class="border rounded-md p-2 min-h-[66px] {{ $seat['is_mine'] ? 'border-green-300 bg-green-50' : 'border-gray-100 bg-gray-50' }}">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-xs font-black text-gray-700">{{ $seat['position'] }}</span>
                                                        <span class="text-[9px] uppercase font-bold {{ $seat['team_side'] === $m['team_side'] ? 'text-indigo-600' : 'text-gray-300' }}">
                                                            {{ $seat['team_side'] === 'home' ? __('Home') : __('Away') }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 text-xs font-semibold text-gray-600 min-h-[16px]">
                                                        {{ $seat['occupant_name'] ?? __('Empty') }}
                                                    </div>
                                                    @if($seat['is_mine'])
                                                        <div class="mt-1 text-[10px] font-black uppercase text-green-700">{{ __('You') }}</div>
                                                    @elseif($seat['can_sit'])
                                                        <form method="POST" action="{{ route('scoring.match.sit', [$m['tournament']->id, $m['round']->id, ($m['match']->id ?: $m['match']->home_team_id)]) }}" class="mt-2">
                                                            @csrf
                                                            <input type="hidden" name="room" value="{{ $room['key'] }}">
                                                            <input type="hidden" name="position" value="{{ $seat['position'] }}">
                                                            <button type="submit" class="w-full rounded bg-gray-800 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-white hover:bg-gray-700">
                                                                {{ __('Sit') }}
                                                            </button>
                                                        </form>
                                                    @elseif($seat['occupant_name'])
                                                        <div class="mt-1 text-[10px] font-bold uppercase text-gray-400">{{ __('Taken') }}</div>
                                                    @else
                                                        <div class="mt-1 text-[10px] font-bold uppercase text-gray-300">{{ __('Other team') }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($room['is_seated'])
                                            <a href="{{ route('scoring.room.show', [$m['tournament']->id, $m['round']->id, ($m['match']->id ?: $m['match']->home_team_id), $room['key']]) }}"
                                               class="mt-3 block w-full text-center bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg text-sm uppercase tracking-widest transition-colors shadow-sm">
                                                {{ __('Enter Scores') }}
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
