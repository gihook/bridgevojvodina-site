<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-900">
                <div class="flex justify-center mb-6">
                    <x-application-logo class="h-32 w-auto" />
                </div>
                <h1 class="text-3xl font-bold mb-4">{{ __('Welcome to Bridge Savez Vojvodine') }}</h1>
                <p class="text-lg">{{ __('Explore our clubs, players, events and tournaments.') }}</p>
                
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <a href="{{ route('clubs.index') }}" class="block p-6 bg-blue-600 hover:bg-blue-700 rounded-lg transition text-center shadow-md group">
                        <h2 class="text-xl font-bold text-white mb-2">{{ __('Clubs') }}</h2>
                        <p class="text-4xl font-extrabold text-white mb-2">{{ $stats['clubs'] }}</p>
                        <p class="text-white opacity-90 group-hover:opacity-100 transition">{{ __('View all bridge clubs in Vojvodina') }}</p>
                    </a>
                    <a href="{{ route('players.index') }}" class="block p-6 bg-green-600 hover:bg-green-700 rounded-lg transition text-center shadow-md group">
                        <h2 class="text-xl font-bold text-white mb-2">{{ __('Players') }}</h2>
                        <p class="text-4xl font-extrabold text-white mb-2">{{ $stats['players'] }}</p>
                        <p class="text-white opacity-90 group-hover:opacity-100 transition">{{ __('Browse registered players') }}</p>
                    </a>
                    <a href="{{ route('events.index') }}" class="block p-6 bg-amber-500 hover:bg-amber-600 rounded-lg transition text-center shadow-md group">
                        <h2 class="text-xl font-bold text-white mb-2">{{ __('Events') }}</h2>
                        <p class="text-4xl font-extrabold text-white mb-2">{{ $stats['events'] }}</p>
                        <p class="text-white opacity-90 group-hover:opacity-100 transition">{{ __('Check out upcoming and past events') }}</p>
                    </a>
                    <a href="{{ route('tournaments.index') }}" class="block p-6 bg-purple-600 hover:bg-purple-700 rounded-lg transition text-center shadow-md group">
                        <h2 class="text-xl font-bold text-white mb-2">{{ __('Tournaments') }}</h2>
                        <p class="text-4xl font-extrabold text-white mb-2">{{ $stats['tournaments'] }}</p>
                        <p class="text-white opacity-90 group-hover:opacity-100 transition">{{ __('Check out upcoming and past tournaments') }}</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
