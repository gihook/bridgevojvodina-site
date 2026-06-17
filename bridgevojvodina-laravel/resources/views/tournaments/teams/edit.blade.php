<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Team') }}: {{ $team->name }}
            </h2>
            <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Team Info (Rename) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Team Details') }}</h3>
                    <form method="POST" action="{{ route('tournaments.teams.update', [$tournament, $team->id]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="flex gap-4 items-end">
                            <div class="flex-1">
                                <x-input-label for="name" :value="__('Team Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $team->name)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>
                            <x-primary-button>
                                {{ __('Update Name') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Roster -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Team Roster') }}</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Player') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Captain') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentPlayers as $player)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($team->captain_id == $player->id)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ __('Captain') }}
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('tournaments.teams.captain.set', [$tournament, $team->id, $player->id]) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 font-semibold uppercase tracking-wider">
                                                        {{ __('Set as Captain') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form method="POST" action="{{ route('tournaments.teams.players.remove', [$tournament, $team->id, $player->id]) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                @if($currentPlayers->isEmpty())
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                            {{ __('No players in this team.') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Player -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Add Player to Team') }}</h3>
                    @php
                        $playerOptions = $availablePlayers->map(function ($player) {
                            $club = $player->club ? ' - ' . $player->club->name : '';

                            return [
                                'id' => $player->id,
                                'label' => trim($player->last_name . ' ' . $player->first_name . $club . ' #' . $player->id),
                            ];
                        })->values();
                    @endphp
                    <form
                        method="POST"
                        action="{{ route('tournaments.teams.players.add', [$tournament, $team->id]) }}"
                        x-data="{
                            query: '',
                            playerId: '',
                            players: @js($playerOptions),
                            syncPlayer() {
                                const selected = this.players.find((player) => player.label === this.query);
                                this.playerId = selected ? selected.id : '';
                                this.$refs.playerSearch.setCustomValidity('');
                            },
                            submitForm(event) {
                                this.syncPlayer();
                                if (!this.playerId) {
                                    event.preventDefault();
                                    this.$refs.playerSearch.setCustomValidity(@js(__('Choose a player from the list.')));
                                    this.$refs.playerSearch.reportValidity();
                                }
                            }
                        }"
                        @submit="submitForm"
                    >
                        @csrf
                        <div class="flex gap-4 items-end">
                            <div class="flex-1">
                                <x-input-label for="player_search" :value="__('Type Player Name')" />
                                <input type="hidden" name="player_id" :value="playerId">
                                <input
                                    id="player_search"
                                    x-ref="playerSearch"
                                    x-model="query"
                                    @input="syncPlayer"
                                    @change="syncPlayer"
                                    list="available_players"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    placeholder="{{ __('Start typing a player name...') }}"
                                    autocomplete="off"
                                    required
                                >
                                <datalist id="available_players">
                                    @foreach($availablePlayers as $player)
                                        <option value="{{ trim($player->last_name . ' ' . $player->first_name . ($player->club ? ' - ' . $player->club->name : '') . ' #' . $player->id) }}"></option>
                                    @endforeach
                                </datalist>
                                <x-input-error class="mt-2" :messages="$errors->get('player_id')" />
                            </div>
                            <x-secondary-button type="submit">
                                {{ __('Add Player') }}
                            </x-secondary-button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ __('Only players not registered in other teams of this tournament are shown.') }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
