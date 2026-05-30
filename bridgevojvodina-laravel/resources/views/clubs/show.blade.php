<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('clubs.index') }}" class="text-blue-500 hover:text-blue-700">{{ __('Clubs') }}</a>
            <span class="mx-2 text-gray-500">/</span>
            {{ $club->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                @if(auth()->user()?->isAdmin())
                    <div class="mb-6 flex space-x-4">
                        <a href="{{ route('clubs.edit', $club) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            {{ __('Edit') }}
                        </a>
                        <form action="{{ route('clubs.destroy', $club) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition" onclick="return confirm('Are you sure?')">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><strong>{{ __('City') }}:</strong> {{ $club->city }}</p>
                        <p><strong>{{ __('Address') }}:</strong> {{ $club->address }}</p>
                        <p><strong>{{ __('Representative') }}:</strong> {{ $club->representative }}</p>
                    </div>
                    <div>
                        <p><strong>{{ __('Email') }}:</strong> {{ $club->email }}</p>
                        <p><strong>{{ __('Phone') }}:</strong> {{ $club->phone }}</p>
                        <p><strong>{{ __('Status') }}:</strong> {{ $club->status }}</p>
                        @if($club->link)
                            <p><strong>{{ __('Link') }}:</strong> <a href="{{ $club->link }}" target="_blank" class="text-blue-500 underline">{{ $club->link }}</a></p>
                        @endif
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Players') }}</h3>
                    <ul class="list-disc pl-5">
                        @foreach($club->players as $player)
                            <li>{{ $player->first_name }} {{ $player->last_name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
