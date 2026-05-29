<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bridž savez Vojvodine') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- SEO Meta Tags -->
        <meta name="description" content="Zvanična prezentacija Bridž saveza Vojvodine. Informacije o klubovima, igračima i predstojećim bridž događajima u Vojvodini.">
        <meta name="keywords" content="bridž, savez, vojvodina, bridge, savez vojvodine, bridž klubovi, bridž igrači, turniri, novi sad">
        <meta name="author" content="Bridž savez Vojvodine">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Bridž savez Vojvodine') }}">
        <meta property="og:description" content="Zvanična prezentacija Bridž saveza Vojvodine. Informacije o klubovima, igračima i predstojećim bridž događajima u Vojvodini.">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ url()->current() }}">
        <meta property="twitter:title" content="{{ config('app.name', 'Bridž savez Vojvodine') }}">
        <meta property="twitter:description" content="Zvanična prezentacija Bridž saveza Vojvodine. Informacije o klubovima, igračima i predstojećim bridž događajima u Vojvodini.">
        <meta property="twitter:image" content="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <footer class="bg-white border-t border-gray-100 py-10 text-gray-500 text-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-4 md:mb-0">
                            <x-application-logo class="h-8 w-auto grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition duration-300" />
                        </div>
                        <div class="flex space-x-6 mb-4 md:mb-0 text-gray-600">
                            <a href="{{ url('/') }}" class="hover:text-blue-600 transition">{{ __('Home') }}</a>
                            <a href="{{ route('clubs.index') }}" class="hover:text-blue-600 transition">{{ __('Clubs') }}</a>
                            <a href="{{ route('players.index') }}" class="hover:text-blue-600 transition">{{ __('Players') }}</a>
                            <a href="{{ route('events.index') }}" class="hover:text-blue-600 transition">{{ __('Events') }}</a>
                            <a href="{{ route('contact') }}" class="hover:text-blue-600 transition">{{ __('Contact') }}</a>
                        </div>
                        <div class="text-xs">
                            &copy; {{ date('Y') }} Bridge Savez Vojvodine. {{ __('All rights reserved.') }}
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
