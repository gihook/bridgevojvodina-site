<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tournament Configurations') }}
            </h2>
            @can('create', App\Models\Tournament::class)
                <a href="{{ route('tournaments.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Add Tournament') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="space-y-4">
                        @forelse ($tournaments as $tournament)
                            <div class="border-b pb-4 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-bold">
                                            <a href="{{ route('tournaments.edit', $tournament) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $tournament->title }}
                                            </a>
                                            <span class="ms-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                {{ __('Draft') }}
                                            </span>
                                        </h3>
                                        <p class="text-gray-600">{{ $tournament->description ?? __('Active tournament draft.') }}</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        @can('update', $tournament)
                                            <a href="{{ route('tournaments.edit', $tournament) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Edit') }}</a>
                                        @endcan
                                        @can('delete', $tournament)
                                            <form method="POST" action="{{ route('tournaments.destroy', $tournament) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-900" onclick="return confirm('{{ __('Are you sure?') }}')">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 italic">{{ __('No tournament configurations found.') }}</p>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $tournaments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
