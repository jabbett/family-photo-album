<x-layouts.photo :title="$title">
    <x-photo-header :show-navigation="true" :prev-photo="$prevPhoto" :next-photo="$nextPhoto" />

        <main class="w-full px-0 py-0">
            <!-- Flat meta header: uploader + relative date (no card) -->
            <div id="photoMetaHeader" class="max-w-3xl mx-auto flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-700">
                        {{ $photo->user?->initials() }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $photo->user?->name }}</div>
                        <div class="text-xs text-gray-500">{{ optional($photo->taken_at ?? $photo->created_at)->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('photos.download', $photo) }}" class="p-2 rounded-lg hover:bg-gray-100" title="Download">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                    </a>
                    <button id="shareButton" class="p-2 rounded-lg hover:bg-gray-100" title="Share">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share2 w-5 h-5 text-gray-700"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Photo stage sized to viewport minus header/meta/footer; caption overlays on photo -->
            <div id="photoStage" class="relative bg-black overflow-hidden" style="height: calc(100dvh - var(--photo-header-h, 0px) - var(--photo-meta-h, 0px) - var(--photo-footer-h, 0px)); max-height: calc(100dvh - var(--photo-header-h, 0px) - var(--photo-meta-h, 0px) - var(--photo-footer-h, 0px));">
                <img src="{{ $photo->original_url }}" alt="{{ $photo->caption ?? 'Photo' }}" class="w-full h-full object-contain select-none" draggable="false">
                @if($photo->caption)
                    <div class="absolute bottom-0 left-0 right-0 px-4 sm:px-6 py-4">
                        <div class="max-w-3xl mx-auto">
                            <div class="inline-block bg-black/60 text-white text-sm px-3 py-2 rounded-md">
                                {{ $photo->caption }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </main>

        <!-- Fixed footer with date and actions -->
        <footer id="photoFooter" class="fixed bottom-0 left-0 right-0 full-w inset-x-0 bg-white border-t border-gray-200">
            <div id="photoFooterInner" class="max-w-3xl mx-auto">
                <div class="flex items-center justify-between px-4 py-3 text-sm text-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="font-medium">{{ optional($photo->taken_at ?? $photo->created_at)->format('j M Y') }}</span>
                    </div>
                    
                    <div class="flex items-center gap-1 sm:gap-2">
                        @auth
                            @if(auth()->user()->isAdmin() || auth()->id() === $photo->user_id)
                                <a href="{{ route('photos.edit', $photo) }}" class="p-2 rounded-lg hover:bg-gray-100" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil w-5 h-5 text-gray-700"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                </a>
                                <form method="POST" action="{{ route('photos.destroy', $photo) }}" onsubmit="return confirm('Delete this photo? This cannot be undone.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg hover:bg-red-50" title="Delete">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 001-1V5a1 1 0 011-1h4a1 1 0 011 1v1a1 1 0 001 1m-8 0h10"/></svg>
                                    </button>
                                </form>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </footer>

        @push('scripts')
            <script>
                function setPhotoLayoutVars() {
                    const headerEl = document.querySelector('header');
                    const metaEl = document.getElementById('photoMetaHeader');
                    const footerEl = document.getElementById('photoFooter');
                    const mainEl = document.querySelector('main');
                    const root = document.documentElement;

                    const headerH = headerEl ? headerEl.offsetHeight : 0;
                    const metaH = metaEl ? metaEl.offsetHeight : 0;
                    const footerH = footerEl ? footerEl.offsetHeight : 0;
                    const contentW = mainEl ? mainEl.clientWidth : 0;

                    root.style.setProperty('--photo-header-h', headerH + 'px');
                    root.style.setProperty('--photo-meta-h', metaH + 'px');
                    root.style.setProperty('--photo-footer-h', footerH + 'px');
                    if (contentW) {
                        root.style.setProperty('--photo-container-w', contentW + 'px');
                    }
                    // no left offset needed; center with auto margins to avoid rounding drift at some zoom levels
                }

                document.addEventListener('DOMContentLoaded', function() {
                    setPhotoLayoutVars();
                    window.addEventListener('resize', setPhotoLayoutVars);

                    // Initialize photo sharing functionality
                    if (window.PhotoAlbum?.initPhotoShare) {
                        window.PhotoAlbum.initPhotoShare();
                    }

                    // Initialize swipe gestures with navigation URLs
                    if (window.PhotoAlbum?.initPhotoSwipe) {
                        window.PhotoAlbum.initPhotoSwipe({
                            prevPhotoUrl: @json($prevPhoto ? route('photos.show', $prevPhoto) : null),
                            nextPhotoUrl: @json($nextPhoto ? route('photos.show', $nextPhoto) : null)
                        });
                    }
                });
            </script>
        @endpush
</x-layouts.photo>


