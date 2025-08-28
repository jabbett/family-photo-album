<x-layouts.photo>
    <x-photo-header />

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            @auth
                <!-- Authenticated User View -->
                <div class="mb-8">
                    @if(empty($photos))
                        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-600">No photos yet. Use the Upload button above to add your first photo.</div>
                    @else
                        <div id="photo-grid" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach($photos as $photo)
                                <a data-photo-id="{{ $photo->id }}" href="{{ route('photos.show', $photo) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden block">
                                    <img src="{{ $photo->thumbnail_url ?? $photo->original_url }}" alt="{{ $photo->caption ?? 'Photo' }}" class="w-full aspect-square object-cover" />
                                </a>
                            @endforeach
                        </div>
                        @if(!empty($nextPage))
                            <div id="infinite-scroll-sentinel" class="h-10"></div>
                            <div class="mt-6 text-center">
                                <button id="load-more-button" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    <span>Load more</span>
                                </button>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Logout at bottom for rare action -->
                <div class="mt-10 flex justify-center">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 hover:text-gray-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Log out</span>
                        </button>
                    </form>
                </div>
            @else
                <!-- Public Visitor View -->
                <div class="max-w-5xl mx-auto">
                    @if(empty($photos))
                        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-600">No photos have been uploaded yet. Please check back soon!</div>
                    @else
                        <div id="photo-grid" class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            @foreach($photos as $photo)
                                <a data-photo-id="{{ $photo->id }}" href="{{ route('photos.show', $photo) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden block">
                                    <img src="{{ $photo->thumbnail_url ?? $photo->original_url }}" alt="{{ $photo->caption ?? 'Photo' }}" class="w-full aspect-square object-cover" />
                                </a>
                            @endforeach
                        </div>
                        @if(!empty($nextPage))
                            <div id="infinite-scroll-sentinel" class="h-10"></div>
                            <div class="mt-6 text-center">
                                <button id="load-more-button" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    <span>Load more</span>
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            @endauth
        </main>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.PhotoAlbum?.initPhotoAlbum) {
                        window.PhotoAlbum.initPhotoAlbum({
                            nextPage: {{ $nextPage ? (int) $nextPage : 'null' }},
                            perPage: {{ (int) ($perPage ?? 20) }},
                            feedUrl: '{{ route('photos.feed') }}'
                        });
                    }
                });
            </script>
        @endpush
</x-layouts.photo>
