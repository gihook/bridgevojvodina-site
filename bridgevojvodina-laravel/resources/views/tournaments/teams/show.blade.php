<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Team') }}: {{ $team->name }}
            </h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('tournaments.show', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    {{ __('Back to Tournament') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Team Members') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($players as $player)
                            <div class="p-4 border rounded-lg flex justify-between items-center bg-gray-50">
                                <div>
                                    <div class="font-semibold">{{ $player->first_name }} {{ $player->last_name }}</div>
                                    @if ($player->id == $team->captain_id)
                                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">{{ __('Captain') }}</span>
                                    @endif
                                </div>
                                <div class="text-gray-500 text-sm">
                                    {{ $player->club?->name }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
