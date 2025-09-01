<x-layouts.base :htmlClass="$htmlClass ?? 'dark'" :bodyClass="$bodyClass ?? 'min-h-screen bg-gray-50 dark:bg-zinc-900'">
    <header class="bg-white border-b border-gray-200 dark:bg-zinc-900 dark:border-zinc-700">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <x-app-logo-icon class="w-8 h-8 aspect-square flex-none" />
                        <div>
                            <h1 class="text-base sm:text-lg font-semibold leading-[1] text-gray-900 dark:text-zinc-100">
                                {{ \App\Models\Setting::getValue('site_title', 'Family Photo Album') }}
                            </h1>
                            <p class="hidden sm:block text-xs text-gray-600 dark:text-zinc-400">
                                {{ \App\Models\Setting::getValue('site_subtitle', 'Sharing our adventures abroad') }}
                            </p>
                        </div>
                    </a>
                </div>

                @auth
                    <div class="flex items-center gap-3">
                        <a href="{{ route('photos.upload.show') }}" class="bg-blue-600 text-white px-2 md:px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center gap-0 md:gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                            <span class="hidden md:inline">Upload</span>
                        </a>
                        <a href="{{ route('settings.profile') }}" class="w-8 h-8 aspect-square rounded-full bg-gray-100 dark:bg-zinc-800 flex-none flex items-center justify-center text-xs font-medium text-gray-700 dark:text-zinc-200 hover:bg-gray-200 dark:hover:bg-zinc-700">
                            {{ auth()->user()->initials() }}
                        </a>
                    </div>
                @else
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="{{ route('login') }}" class="bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 flex items-center space-x-2">
                            <span>Sign In</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                        @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="hidden sm:inline-block border border-blue-600 text-blue-600 px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                            Register
                        </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        {{ $slot }}
    </main>
</x-layouts.base>


