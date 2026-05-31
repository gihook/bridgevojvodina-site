<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Board Set') }}: {{ $boardSet->name }}
            </h2>
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('tournaments.board-sets.destroy', [$tournament, $boardSet]) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">
                        {{ __('Delete Board Set') }}
                    </x-danger-button>
                </form>
                <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Tournament') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" 
                 x-data="{ 
                    currentIdx: 0, 
                    total: {{ $boards->count() }},
                    next() { if (this.currentIdx < this.total - 1) this.currentIdx++; },
                    prev() { if (this.currentIdx > 0) this.currentIdx--; }
                 }">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-6">
                            <h3 class="text-2xl font-bold text-gray-800">{{ __('Board') }} <span x-text="document.querySelectorAll('.board-container')[currentIdx].dataset.number"></span></h3>
                            <div class="flex items-center gap-4">
                                <span class="px-3 py-1 bg-white border border-gray-200 rounded-full text-xs font-semibold text-gray-600">
                                    {{ __('Vuln') }}: <span class="font-black" :class="{
                                        'text-red-600': document.querySelectorAll('.board-container')[currentIdx].dataset.vuln === 'All',
                                        'text-green-700': document.querySelectorAll('.board-container')[currentIdx].dataset.vuln === 'NS',
                                        'text-orange-600': document.querySelectorAll('.board-container')[currentIdx].dataset.vuln === 'EW',
                                        'text-gray-600': document.querySelectorAll('.board-container')[currentIdx].dataset.vuln === 'None'
                                    }" x-text="document.querySelectorAll('.board-container')[currentIdx].dataset.vuln_trans"></span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-sm text-gray-500 mr-4 font-mono">
                                <span x-text="currentIdx + 1"></span> / <span x-text="total"></span>
                            </div>
                            <x-secondary-button @click="prev()" x-bind:disabled="currentIdx === 0">
                                {{ __('Previous') }}
                            </x-secondary-button>
                            <x-secondary-button @click="next()" x-bind:disabled="currentIdx === total - 1">
                                {{ __('Next') }}
                            </x-secondary-button>
                        </div>
                    </div>

                    <div class="relative">
                        @foreach($boards as $index => $board)
                            <div class="board-container transition-opacity duration-300" 
                                 x-show="currentIdx === {{ $index }}" 
                                 x-cloak
                                 data-number="{{ $board->board_number }}"
                                 data-vuln="{{ $board->vulnerability }}"
                                 data-vuln_trans="{{ __($board->vulnerability) }}">
                                
                                <div class="flex flex-col items-center justify-center gap-8 py-8">
                                    <!-- North -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="text-[10px] uppercase font-bold text-blue-400 text-center">{{ __('North') }}</div>
                                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-md transform hover:scale-105 transition-transform duration-200">
                                            <x-bridge-hand :hand="$board->cards_north" />
                                        </div>
                                    </div>

                                    <!-- Middle: West & East -->
                                    <div class="flex items-center gap-8 md:gap-32 lg:gap-48">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="text-[10px] uppercase font-bold text-red-400 text-center">{{ __('West') }}</div>
                                            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-md transform hover:scale-105 transition-transform duration-200">
                                                <x-bridge-hand :hand="$board->cards_west" />
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-2">
                                            <div class="text-[10px] uppercase font-bold text-red-400 text-center">{{ __('East') }}</div>
                                            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-md transform hover:scale-105 transition-transform duration-200">
                                                <x-bridge-hand :hand="$board->cards_east" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- South -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="text-[10px] uppercase font-bold text-blue-400 text-center">{{ __('South') }}</div>
                                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-md transform hover:scale-105 transition-transform duration-200">
                                            <x-bridge-hand :hand="$board->cards_south" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
