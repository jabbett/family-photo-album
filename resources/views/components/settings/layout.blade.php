<div class="w-full">
    <flux:heading size="xl" level="1">{{ $heading ?? '' }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ $subheading ?? '' }}</flux:subheading>

    <div class="mt-5 w-full max-w-lg">
        {{ $slot }}
    </div>
</div>
