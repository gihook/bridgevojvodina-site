<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $tournament->title }}
                @if($tournament->is_completed)
                    <span class="ms-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('Completed') }}
                    </span>
                @else
                    <span class="ms-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ __('In Progress') }}
                    </span>
                @endif
            </h2>
            <div class="flex space-x-2">
                @can('update', $tournament)
                    <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        {{ __('Edit') }}
                    </a>
                @endcan
                <a href="{{ route('tournaments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    {{ __('Back to List') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Description') }}</h3>
                        <p class="mt-1 text-gray-600">{{ $tournament->description }}</p>
                    </div>

                    <div class="border-t pt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Tournament Details') }}</h3>
                        <div class="prose max-w-none">
                            {!! Str::markdown($tournament->details) !!}
                        </div>
                    </div>

                    <div class="mt-8 pt-4 border-t text-sm text-gray-500">
                        {{ __('Created by') }}: {{ $tournament->creator->name }} | {{ __('Last updated') }}: {{ $tournament->updated_at->format('M d, Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
