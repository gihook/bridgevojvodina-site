<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Contact') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <h3 class="text-lg font-semibold mb-4">{{ __('Bridge Savez Vojvodine') }}</h3>
                <p>{{ __('Address') }}: Raše Radujkova 4, 21000 Novi Sad</p>
                <p>{{ __('Email') }}: <a href="mailto:ivbdva@gmail.com" class="text-blue-500 underline">ivbdva@gmail.com</a></p>
                <p>{{ __('Phone') }}: 060 687 8746</p>
                
                <div class="mt-8">
                    <h4 class="font-semibold">{{ __('Follow us on Facebook') }}:</h4>
                    <a href="https://www.facebook.com/bridgesavezvojvodine/" target="_blank" class="text-blue-500 underline">
                        https://www.facebook.com/bridgesavezvojvodine/
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
