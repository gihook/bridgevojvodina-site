<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-900">
                <h1 class="text-3xl font-bold mb-4">{{ __('Welcome to Bridge Savez Vojvodine') }}</h1>
                <p class="text-lg">{{ __('Explore our clubs, players and events.') }}</p>
                
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="{{ route('clubs.index') }}" class="block p-6 bg-blue-100 hover:bg-blue-200 rounded-lg transition text-center">
                        <h2 class="text-xl font-semibold">{{ __('Clubs') }}</h2>
                        <p class="text-3xl font-bold my-2">{{ $stats['clubs'] }}</p>
                        <p>{{ __('View all bridge clubs in Vojvodina') }}</p>
                    </a>
                    <a href="{{ route('players.index') }}" class="block p-6 bg-green-100 hover:bg-green-200 rounded-lg transition text-center">
                        <h2 class="text-xl font-semibold">{{ __('Players') }}</h2>
                        <p class="text-3xl font-bold my-2">{{ $stats['players'] }}</p>
                        <p>{{ __('Browse registered players') }}</p>
                    </a>
                    <a href="{{ route('events.index') }}" class="block p-6 bg-yellow-100 hover:bg-yellow-200 rounded-lg transition text-center">
                        <h2 class="text-xl font-semibold">{{ __('Events') }}</h2>
                        <p class="text-3xl font-bold my-2">{{ $stats['events'] }}</p>
                        <p>{{ __('Check out upcoming and past events') }}</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
