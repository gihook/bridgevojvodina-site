<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('events.index') }}" class="text-blue-500 hover:text-blue-700">{{ __('Events') }}</a>
            <span class="mx-2 text-gray-500">/</span>
            {{ $event->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                @if(auth()->user()?->isAdmin())
                    <div class="mb-6 flex space-x-4">
                        <a href="{{ route('events.edit', $event) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            {{ __('Edit') }}
                        </a>
                        <form action="{{ route('events.destroy', $event) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition" onclick="return confirm('Are you sure?')">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                @endif
                <p><strong>{{ __('Date') }}:</strong> {{ $event->date }}</p>
                <p><strong>{{ __('Club') }}:</strong> <a href="{{ route('clubs.show', $event->club) }}" class="text-blue-500 underline">{{ $event->club->name }}</a></p>
                <div class="mt-8 border-t pt-6">
                    <div class="prose max-w-none">
                        {!! Illuminate\Support\Str::markdown($event->description ?? '') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
