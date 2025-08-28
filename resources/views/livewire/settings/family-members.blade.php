<?php

use App\Models\AllowedEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;

new class extends Component {
    #[Rule('required|email|max:255')]
    public string $new_email = '';

    #[Rule('nullable|string|max:255')]
    public string $new_email_name = '';

    public function mount(): void
    {
        // Initialize empty form
    }

    public function addAllowedEmail(): void
    {
        $this->validate([
            'new_email' => 'required|email|max:255|unique:allowed_emails,email',
            'new_email_name' => 'nullable|string|max:255',
        ]);

        AllowedEmail::create([
            'email' => $this->new_email,
            'name' => $this->new_email_name ?: null,
        ]);

        $this->new_email = '';
        $this->new_email_name = '';

        $this->dispatch('allowed-email-added');
    }

    public function toggleEmailStatus(AllowedEmail $email): void
    {
        $email->update(['is_active' => !$email->is_active]);
    }

    public function deleteEmail(AllowedEmail $email): void
    {
        $email->delete();
    }

    public function getAllowedEmails()
    {
        return AllowedEmail::orderBy('email')->get();
    }
}; ?>

<section class="w-full">
    <x-settings.layout :heading="__('Family Members')" :subheading="__('Manage which email addresses can register for your family album')">
        <div class="space-y-6">
            <!-- Add New Email -->
            <form wire:submit="addAllowedEmail" class="flex gap-3">
                <flux:input
                    wire:model="new_email"
                    type="email"
                    :placeholder="__('email@example.com')"
                    class="flex-1"
                />
                <flux:input
                    wire:model="new_email_name"
                    type="text"
                    :placeholder="__('Name (optional)')"
                    class="flex-1"
                />
                <flux:button type="submit" variant="primary">
                    {{ __('Add') }}
                </flux:button>
            </form>

            <!-- Email List -->
            <div class="space-y-3">
                @foreach($this->getAllowedEmails() as $email)
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                {{ $email->email }}
                            </div>
                            @if($email->name)
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 truncate">
                                    {{ $email->name }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-3 ml-4">
                            @if($email->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                            
                            <flux:button
                                wire:click="toggleEmailStatus({{ $email->id }})"
                                variant="filled"
                                size="sm"
                            >
                                {{ $email->is_active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                            
                            <flux:button
                                wire:click="deleteEmail({{ $email->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this email?') }}"
                                variant="danger"
                                size="sm"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-settings.layout>
</section>
