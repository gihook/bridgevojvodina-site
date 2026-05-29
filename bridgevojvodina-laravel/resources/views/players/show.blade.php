<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $player->first_name }} {{ $player->last_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <p><strong>{{ __('First Name') }}:</strong> {{ $player->first_name }}</p>
                <p><strong>{{ __('Last Name') }}:</strong> {{ $player->last_name }}</p>
                <p><strong>{{ __('Club') }}:</strong> <a href="{{ route('clubs.show', $player->club) }}" class="text-blue-500 underline">{{ $player->club->name }}</a></p>
            </div>
        </div>
    </div>
</x-app-layout>
