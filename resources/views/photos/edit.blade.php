<x-layouts.photo title="Edit Photo">
    <x-simple-header :back-url="route('photos.show', $photo)" title="Edit Photo">
        <div class="mt-6 rounded-lg bg-white border border-gray-200 p-4">
            <img src="{{ $photo->thumbnail_url ?? $photo->original_url }}" alt="Photo" class="w-full rounded aspect-square object-cover" />

            <form class="mt-4" method="POST" action="{{ route('photos.update', $photo) }}">
                @csrf
                @method('PATCH')
                
                <label class="block text-sm font-medium text-gray-700">Caption</label>
                <textarea 
                    name="caption" 
                    rows="3" 
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500 @error('caption') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror" 
                    placeholder="Say something about this photo..."
                    maxlength="500"
                >{{ old('caption', $photo->caption) }}</textarea>
                
                @error('caption')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('photos.show', $photo) }}" class="flex-1 rounded-lg bg-gray-100 px-4 py-3 text-center text-gray-700 font-medium hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </x-simple-header>
</x-layouts.photo>