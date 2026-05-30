<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $tournament->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if ($tournament->team_results)
                @include('tournaments.partials.standings', ['results' => $tournament->team_results])
                @include('tournaments.partials.matches', ['results' => $tournament->team_results])
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p>{{ __('Nema dostupnih rezultata za ovaj turnir.') }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ __('Detalji turnira') }}</h3>
                    <div class="prose max-w-none">
                        {{ $tournament->description }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
