<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('users.index') }}" class="text-blue-500 hover:text-blue-700">{{ __('Users') }}</a>
            <span class="mx-2 text-gray-500">/</span>
            {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                @if(auth()->user()?->isAdmin())
                    <div class="mb-6 flex space-x-4">
                        <a href="{{ route('users.edit', $user) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            {{ __('Edit') }}
                        </a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition" onclick="return confirm('Are you sure?')">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
                <p><strong>{{ __('Name') }}:</strong> {{ $user->name }}</p>
                <p><strong>{{ __('Email') }}:</strong> {{ $user->email }}</p>
                <p><strong>{{ __('Role') }}:</strong> {{ $user->role }}</p>
                <p><strong>{{ __('Associated Player') }}:</strong> {{ $user->player ? $user->player->last_name . ' ' . $user->player->first_name : __('None') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
