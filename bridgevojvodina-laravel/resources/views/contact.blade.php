<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Contact') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">{{ __('Bridge Savez Vojvodine') }}</h3>
                        
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold">Jovana Maričić</h4>
                            <p class="text-gray-600 italic">{{ __('President') }}</p>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-lg font-semibold">Stevan Miškov</h4>
                            <p class="text-gray-600 italic">{{ __('Secretary') }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="rounded-lg overflow-hidden shadow-md border border-gray-200 h-[430px]">
                            <iframe 
                                frameborder="0" 
                                height="100%" 
                                scrolling="no" 
                                src="https://maps.google.com?saddr=Bridž savez Vojvodine, Danila Kiša 25, 21000 Novi Sad&z=12&output=embed" 
                                width="100%">
                            </iframe>
                        </div>
                        
                        <div class="mt-8 space-y-2">
                            <p><strong>{{ __('Address') }}:</strong> Danila Kiša 25, 21000 Novi Sad</p>
                            <p><strong>{{ __('Email') }}:</strong> <a href="mailto:bridge.savez.vojvodine@gmail.com" class="text-blue-500 underline">bridge.savez.vojvodine@gmail.com</a></p>
                            
                            <div class="mt-4">
                                <h4 class="font-semibold">{{ __('Follow us on Facebook') }}:</h4>
                                <a href="https://www.facebook.com/bridgesavezvojvodine/" target="_blank" class="text-blue-600 hover:underline">
                                    https://www.facebook.com/bridgesavezvojvodine/
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
