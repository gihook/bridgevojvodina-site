<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $event->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <p><strong>{{ __('Date') }}:</strong> {{ $event->date }}</p>
                <p><strong>{{ __('Club') }}:</strong> <a href="{{ route('clubs.show', $event->club) }}" class="text-blue-500 underline">{{ $event->club->name }}</a></p>
                <div class="mt-4">
                    <p><strong>{{ __('Description') }}:</strong></p>
                    <div class="mt-2 whitespace-pre-wrap">{{ $event->description }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
