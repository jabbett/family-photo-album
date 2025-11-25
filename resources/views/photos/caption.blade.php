<x-layouts.photo title="Add Caption">
    <x-simple-header :back-url="route('home')" title="Add Details">
        <div class="mt-6">
            {{-- Photo Preview Grid --}}
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">
                    {{ $post->photos->count() === 1 ? '1 photo' : $post->photos->count() . ' photos' }}
                </p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($post->photos as $photo)
                        <div class="relative aspect-square rounded overflow-hidden bg-gray-100">
                            <img
                                src="{{ $photo->thumbnail_url ?? $photo->original_url }}"
                                alt="Photo {{ $loop->iteration }}"
                                class="w-full h-full object-cover"
                            />
                            @if($post->photos->count() > 1)
                                <div class="absolute top-1 right-1 bg-black/60 text-white text-xs px-2 py-1 rounded">
                                    {{ $loop->iteration }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Caption Form --}}
            <form method="POST" action="{{ route('posts.caption.handle', $post) }}" class="rounded-lg bg-white border border-gray-200 p-4">
                @csrf

                {{-- Caption --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Caption (optional)</label>
                    <textarea
                        name="caption"
                        rows="3"
                        class="block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Say something about {{ $post->photos->count() === 1 ? 'this photo' : 'these photos' }}...">{{ old('caption', $post->caption) }}</textarea>
                </div>

                {{-- Date/Time --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date & Time</label>
                    <input
                        type="datetime-local"
                        name="display_date"
                        value="{{ old('display_date', $post->display_date?->format('Y-m-d\TH:i')) }}"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p class="mt-1 text-xs text-gray-500">Date from first photo's EXIF data, or current time if unavailable</p>
                </div>

                <button type="submit" class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700">
                    {{ $post->photos->first()?->width === $post->photos->first()?->height ? 'Publish Post' : 'Continue to Crop' }}
                </button>
            </form>
        </div>
    </x-simple-header>
</x-layouts.photo>
