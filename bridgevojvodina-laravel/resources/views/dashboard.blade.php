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
                    <h3 class="text-lg font-semibold mb-4">{{ auth()->user()->isAdmin() ? __('Manage your data') : __('View data') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('clubs.index') }}" class="p-4 bg-blue-100 rounded-lg hover:bg-blue-200 transition text-center font-semibold text-blue-800">
                            {{ auth()->user()->isAdmin() ? __('Manage Clubs') : __('View Clubs') }}
                        </a>
                        <a href="{{ route('players.index') }}" class="p-4 bg-blue-100 rounded-lg hover:bg-blue-200 transition text-center font-semibold text-blue-800">
                            {{ auth()->user()->isAdmin() ? __('Manage Players') : __('View Players') }}
                        </a>
                        <a href="{{ route('events.index') }}" class="p-4 bg-blue-100 rounded-lg hover:bg-blue-200 transition text-center font-semibold text-blue-800">
                            {{ auth()->user()->isAdmin() ? __('Manage Events') : __('View Events') }}
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" class="p-4 bg-green-100 rounded-lg hover:bg-green-200 transition text-center font-semibold text-green-800">
                                {{ __('Manage Users') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
