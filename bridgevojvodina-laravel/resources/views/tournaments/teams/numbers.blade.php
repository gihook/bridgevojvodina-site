<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Team Numbers') }}: {{ $tournament->title }}
            </h2>
            <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-6 text-sm text-gray-600">
                        {{ __('Assign unique numbers to teams. These numbers are used to determine the pairings in the Berger tables algorithm.') }}
                    </p>

                    <form method="POST" action="{{ route('tournaments.teams.numbers.update', $tournament) }}">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-4">
                            @foreach($teams as $team)
                                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="w-24">
                                        <select 
                                            id="number_{{ $team->id }}" 
                                            name="numbers[{{ $team->id }}]" 
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-center font-bold"
                                        >
                                            <option value="">-</option>
                                            @for($i = 1; $i <= $teams->count(); $i++)
                                                <option value="{{ $i }}" {{ old('numbers.'.$team->id, $team->number) == $i ? 'selected' : '' }}>
                                                    {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label for="number_{{ $team->id }}" class="text-sm font-medium text-gray-700">
                                            {{ $team->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-input-error class="mt-4" :messages="$errors->get('error')" />
                        <x-input-error class="mt-2" :messages="$errors->get('numbers.*')" />

                        <div class="mt-8 flex justify-end">
                            <x-primary-button>
                                {{ __('Save Team Numbers') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
