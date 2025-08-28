@props(['backUrl' => null, 'title' => ''])

<div class="max-w-lg mx-auto p-6">
    @if($backUrl)
        <a href="{{ $backUrl }}" class="text-sm text-gray-600">&larr; Back to Album</a>
    @endif
    @if($title)
        <h1 class="text-2xl font-semibold text-gray-900 mt-4">{{ $title }}</h1>
    @endif
    
    {{ $slot }}
</div>
