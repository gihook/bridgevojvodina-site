<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $tournament->title }}
            </h2>
            @can('update', $tournament)
                <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Edit Tournament') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if ($tournament->team_results)
                @include('tournaments.partials.standings', ['results' => $tournament->team_results])
                @include('tournaments.partials.match_list', ['results' => $tournament->team_results])
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p>{{ __('No results available for this tournament.') }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Tournament details') }}</h3>
                    <div class="prose max-w-none">
                        {!! \Illuminate\Support\Str::markdown($tournament->details ?? '') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
