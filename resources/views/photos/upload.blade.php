<x-layouts.photo title="Upload Photo">
    <x-simple-header :back-url="route('home')" title="Upload a Photo">
        <form id="upload-form" class="mt-6">
            @csrf

            {{-- Error container (for upload failures) --}}
            <div id="error-container" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 hidden"></div>

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- File picker (visible initially) --}}
            <div id="file-picker-container">
                <label for="photo-input" class="block text-sm font-medium text-gray-700">Choose photo</label>
                <input
                    id="photo-input"
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                    type="file" name="photo" accept="image/*,.heic,.heif" required
                />
                <p class="mt-2 text-xs text-gray-500">JPG/PNG/GIF/HEIC up to 10MB. HEIC files will be converted to JPEG.</p>
            </div>

            {{-- Preview + Caption (hidden initially, shown after file selection) --}}
            <div id="preview-container" class="hidden mt-6">
                <div class="rounded-lg bg-white border border-gray-200 p-4">
                    <img id="preview-image" src="" alt="Preview" class="w-full rounded aspect-square object-cover" />

                    <div class="mt-4">
                        <label for="caption-textarea" class="block text-sm font-medium text-gray-700">Caption (optional)</label>
                        <textarea
                            id="caption-textarea"
                            name="caption"
                            rows="3"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Say something about this photo...">{{ old('caption') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Upload status indicator --}}
            <div id="upload-status" class="mt-4 hidden">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <span>Uploading in background...</span>
                </div>
            </div>

            {{-- Continue button (always visible after initialization) --}}
            <button
                id="continue-btn"
                type="button"
                class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Continue
            </button>
        </form>
    </x-simple-header>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.PhotoAlbum?.initPhotoUpload) {
                    window.PhotoAlbum.initPhotoUpload();
                }
            });
        </script>
    @endpush
</x-layouts.photo>
