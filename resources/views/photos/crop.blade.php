<x-layouts.photo title="Crop Photo">
    <x-simple-header :back-url="route('home')" title="Choose Thumbnail Crop">
        <div class="mt-6 rounded-lg bg-white border border-gray-200 p-4">
            <div class="w-full max-w-full overflow-hidden rounded">
                <img id="crop-image" src="{{ $photo->original_url }}" alt="Original" class="max-w-full block" style="image-orientation: from-image;" />
            </div>

            <form id="crop-form" class="mt-4" method="POST" action="{{ route('photos.crop.handle', $photo) }}">
                @csrf
                <input type="hidden" name="crop_x" id="crop_x" />
                <input type="hidden" name="crop_y" id="crop_y" />
                <input type="hidden" name="crop_size" id="crop_size" />
                <p class="text-sm text-gray-600">Drag to position the square crop. Pinch/scroll to zoom. We'll create a square thumbnail from this area.</p>

                <button type="submit" class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700">
                    Continue
                </button>
            </form>
        </div>
    </x-simple-header>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.PhotoAlbum?.initPhotoCrop) {
                        window.PhotoAlbum.initPhotoCrop();
                    }
                });
            </script>
        @endpush
</x-layouts.photo>


