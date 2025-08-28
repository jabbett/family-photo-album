<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;

new class extends Component {
    #[Rule('required|string|max:255')]
    public string $site_title = '';

    #[Rule('required|string|max:255')]
    public string $site_subtitle = '';

    #[Rule('required|string|max:7')]
    public string $theme_color = '#3b82f6';

    public function mount(): void
    {
        $this->site_title = Setting::getValue('site_title', 'Family Photo Album');
        $this->site_subtitle = Setting::getValue('site_subtitle', 'Sharing our adventures abroad');
        $this->theme_color = Setting::getValue('theme_color', '#3b82f6');
    }

    public function updateSettings(): void
    {
        $this->validate();

        Setting::setValue('site_title', $this->site_title);
        Setting::setValue('site_subtitle', $this->site_subtitle);
        Setting::setValue('theme_color', $this->theme_color);

        $this->dispatch('settings-updated');
    }
}; ?>

<section class="w-full">
    <x-settings.layout :heading="__('Site Settings')" :subheading="__('Configure your family photo album settings')">
        <form wire:submit="updateSettings" class="space-y-6">
            <!-- Site Title -->
            <flux:input
                wire:model="site_title"
                :label="__('Site Title')"
                type="text"
                required
                :placeholder="__('Family Photo Album')"
            />

            <!-- Site Subtitle -->
            <flux:input
                wire:model="site_subtitle"
                :label="__('Site Subtitle')"
                type="text"
                required
                :placeholder="__('Sharing our adventures abroad')"
            />

            <!-- Theme Color -->
            <div>
                <flux:input
                    wire:model="theme_color"
                    :label="__('Theme Color')"
                    type="color"
                    required
                />
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Choose the primary color for your family photo album.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Settings') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="settings-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
