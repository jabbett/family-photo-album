<x-layouts.photo title="Edit Post">
    <x-simple-header :back-url="route('photos.show', $post)" back-text="Back to Post" title="Edit Post">
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

            {{-- Edit Form --}}
            <form method="POST" action="{{ route('photos.update', $post) }}" class="rounded-lg bg-white border border-gray-200 p-4">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <!-- Caption Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Caption</label>
                        <textarea
                            name="caption"
                            rows="3"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500 @error('caption') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            placeholder="Say something about {{ $post->photos->count() === 1 ? 'this photo' : 'these photos' }}..."
                            maxlength="2000"
                        >{{ old('caption', $post->caption) }}</textarea>

                        @error('caption')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date/Time Fields -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Post Date & Time</label>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <input
                                    type="date"
                                    name="taken_date"
                                    value="{{ old('taken_date', $post->display_date ? $post->display_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500 @error('taken_date') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                >
                                @error('taken_date')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input
                                    type="time"
                                    name="taken_time"
                                    value="{{ old('taken_time', $post->display_date ? $post->display_date->format('H:i') : '12:00') }}"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500 @error('taken_time') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                >
                                @error('taken_time')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-3">
                    <a href="{{ route('photos.show', $post) }}" class="flex-1 rounded-lg bg-gray-100 px-4 py-3 text-center text-gray-700 font-medium hover:bg-gray-200 transition-colors">
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
