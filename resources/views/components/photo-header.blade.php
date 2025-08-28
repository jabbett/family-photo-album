@props(['showNavigation' => false, 'prevPhoto' => null, 'nextPhoto' => null])

<header class="bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 py-4">
        @if($showNavigation)
            <div class="grid grid-cols-3 items-center">
                <div class="justify-self-start">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-gray-900">&lsaquo; All photos</a>
                </div>
                <div class="justify-self-center text-gray-900 font-semibold truncate">
                    {{ \App\Models\Setting::getValue('site_title', 'Family Photo Album') }}
                </div>
                <div class="justify-self-end flex items-center gap-2">
                    @php
                        $prevDisabled = empty($prevPhoto);
                        $nextDisabled = empty($nextPhoto);
                    @endphp
                    <a href="{{ $prevDisabled ? '#' : route('photos.show', $prevPhoto) }}" 
                       class="p-2 rounded-lg {{ $prevDisabled ? 'opacity-40 cursor-not-allowed bg-gray-50' : 'hover:bg-gray-100' }}" 
                       aria-disabled="{{ $prevDisabled ? 'true' : 'false' }}" 
                       title="Previous">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <a href="{{ $nextDisabled ? '#' : route('photos.show', $nextPhoto) }}" 
                       class="p-2 rounded-lg {{ $nextDisabled ? 'opacity-40 cursor-not-allowed bg-gray-50' : 'hover:bg-gray-100' }}" 
                       aria-disabled="{{ $nextDisabled ? 'true' : 'false' }}" 
                       title="Next">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ \App\Models\Setting::getValue('site_title', 'Family Photo Album') }}
                        </h1>
                        <p class="text-sm text-gray-600">
                            {{ \App\Models\Setting::getValue('site_subtitle', 'Sharing our adventures abroad') }}
                        </p>
                    </div>
                </div>
                
                @auth
                    <div class="flex items-center gap-3">
                        <a href="{{ route('photos.upload.show') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Upload</span>
                        </a>
                        <a href="{{ route('settings.profile') }}" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-700 hover:bg-gray-200">
                            {{ auth()->user()->initials() }}
                        </a>
                    </div>
                @else
                    <div class="flex items-center gap-4">
                        <a href="{{ route('login') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center space-x-2">
                            <span>Sign In</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @endauth
            </div>
        @endif
    </div>
</header>
