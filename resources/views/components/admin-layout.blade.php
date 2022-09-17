<!DOCTYPE html>
<html lang="{{ $page->language ?? 'en' }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="referrer" content="always">

        <title>Admin</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    </head>

    <body>
        @if (Session::has('message'))
        <!-- This example requires Tailwind CSS v2.0+ -->
        <div
             class="bg-indigo-600"
             x-data="{ bannerOpen: true }"
             x-show="bannerOpen">
            <div class="mx-auto max-w-7xl py-3 px-3 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex w-0 flex-1 items-center">
                        <p class="ml-3 truncate font-medium text-white">
                            <span class="md:hidden">{{ Session::get('message') }}</span>
                            <span class="hidden md:inline">{{ Session::get('message') }}</span>
                        </p>
                    </div>
                    <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-3">
                        <button
                                type="button"
                                class="-mr-1 flex rounded-md p-2 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-white sm:-mr-2"
                                x-on:click="bannerOpen = false">
                            <span class="sr-only">Dismiss</span>
                            <!-- Heroicon name: outline/x-mark -->
                            <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-200 font-roboto">
            @include('layouts.sidebar')

            <div class="flex-1 flex flex-col overflow-hidden">
                @include('layouts.header')

                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200">
                    <div class="container mx-auto px-6 py-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>

</html>