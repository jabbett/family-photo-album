<x-layouts.photo title="Upload Photo">
    <x-simple-header :back-url="route('home')" title="Upload a Photo">
        <form class="mt-6" method="POST" action="{{ route('photos.upload.handle') }}" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <label class="block text-sm font-medium text-gray-700">Choose photo</label>
            <input
                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                type="file" name="photo" accept="image/*" required
            />
            <p class="mt-2 text-xs text-gray-500">JPG/PNG/GIF up to 10MB. If uploads fail, increase PHP limits (upload_max_filesize, post_max_size).</p>

            <button type="submit" class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700">
                Continue
            </button>
        </form>
    </x-simple-header>
</x-layouts.photo>


