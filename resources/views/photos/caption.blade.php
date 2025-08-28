<x-layouts.photo title="Add Caption">
    <x-simple-header :back-url="route('home')" title="Add a Caption">
        <div class="mt-6 rounded-lg bg-white border border-gray-200 p-4">
            <img src="{{ $photo->thumbnail_url ?? $photo->original_url }}" alt="Thumbnail" class="w-full rounded aspect-square object-cover" />

            <form class="mt-4" method="POST" action="{{ route('photos.caption.handle', $photo) }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Caption (optional)</label>
                <textarea name="caption" rows="3" class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500" placeholder="Say something about this photo...">{{ old('caption', $photo->caption) }}</textarea>

                <button type="submit" class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700">
                    Save Photo
                </button>
            </form>
        </div>
    </x-simple-header>
</x-layouts.photo>


