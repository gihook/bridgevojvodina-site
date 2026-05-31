<div class="space-y-6" x-data="{ details: {{ json_encode(old('details', $tournament->details ?? '')) }} }">
    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $tournament->title ?? '')" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Description')" />
        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $tournament->description ?? '')" required />
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="block">
        <label for="is_completed" class="inline-flex items-center">
            <input id="is_completed" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_completed" value="1" {{ old('is_completed', $tournament->is_completed ?? false) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Mark as Completed') }}</span>
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_completed')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="details" :value="__('Details (Markdown)')" />
            <textarea id="details" name="details" x-model="details" 
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm h-64"
                required></textarea>
            <x-input-error class="mt-2" :messages="$errors->get('details')" />
        </div>
        <div>
            <x-input-label :value="__('Preview')" />
            <div class="mt-1 p-4 border border-gray-200 rounded-md bg-gray-50 h-64 overflow-y-auto prose max-w-none" 
                x-html="window.marked ? window.marked.parse(details) : ''">
            </div>
        </div>
    </div>
</div>
