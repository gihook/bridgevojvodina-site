<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">{{ (auth()->user()->isAdmin() || auth()->user()->isDirector()) ? __('Manage your data') : __('View data') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <a href="{{ route('clubs.index') }}" class="p-6 bg-blue-600 rounded-lg hover:bg-blue-700 transition text-center font-bold text-white shadow-md">
                            {{ auth()->user()->isAdmin() ? __('Manage Clubs') : __('View Clubs') }}
                        </a>
                        <a href="{{ route('players.index') }}" class="p-6 bg-green-600 rounded-lg hover:bg-green-700 transition text-center font-bold text-white shadow-md">
                            {{ auth()->user()->isAdmin() ? __('Manage Players') : __('View Players') }}
                        </a>
                        <a href="{{ route('events.index') }}" class="p-6 bg-amber-500 rounded-lg hover:bg-amber-600 transition text-center font-bold text-white shadow-md">
                            {{ auth()->user()->isAdmin() ? __('Manage Events') : __('View Events') }}
                        </a>
                        <a href="{{ route('tournaments.index') }}" class="p-6 bg-purple-600 rounded-lg hover:bg-purple-700 transition text-center font-bold text-white shadow-md">
                            {{ (auth()->user()->isAdmin() || auth()->user()->isDirector()) ? __('Manage Tournaments') : __('View Tournaments') }}
                        </a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isDirector())
                            <a href="{{ route('running-tournaments.index') }}" class="p-6 bg-red-600 rounded-lg hover:bg-red-700 transition text-center font-bold text-white shadow-md">
                                {{ __('Running Tournaments') }}
                            </a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" class="p-6 bg-indigo-600 rounded-lg hover:bg-indigo-700 transition text-center font-bold text-white shadow-md">
                                {{ __('Manage Users') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
