<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split', [
    'badge' => 'Protected Area',
    'heading' => 'Confirm it is really you.',
    'subtext' => 'For your security, re-enter your password before continuing to sensitive settings.',
])] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Protected Area</p>
        <h1 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Confirm your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <form class="flex flex-col gap-4" wire:submit="confirmPassword">
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                wire:model="password"
                id="password"
                class="mt-1 block w-full py-2.5"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-2 flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</div>
