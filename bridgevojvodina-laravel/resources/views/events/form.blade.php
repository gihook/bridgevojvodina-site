<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($event) ? __('Edit Event') : __('Add Event') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <form action="{{ isset($event) ? route('events.update', $event) : route('events.store') }}" method="POST">
                    @csrf
                    @if(isset($event))
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-input-label for="title" :value="__('Title')" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $event->title ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                        </div>

                        <div>
                            <x-input-label for="date" :value="__('Date')" />
                            <x-text-input id="date" name="date" type="text" class="mt-1 block w-full" :value="old('date', $event->date ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('date')" />
                        </div>

                        <div>
                            <x-input-label for="club_id" :value="__('Club')" />
                            <select id="club_id" name="club_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                @foreach($clubs as $club)
                                    <option value="{{ $club->id }}" {{ old('club_id', $event->club_id ?? '') == $club->id ? 'selected' : '' }}>{{ $club->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('club_id')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" rows="5">{{ old('description', $event->description ?? '') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
