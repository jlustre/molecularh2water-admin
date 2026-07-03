<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split', [
    'badge' => 'Password Help',
    'heading' => 'Get back into your account.',
    'subtext' => 'Enter your email and we will send you a secure link to choose a new password.',
])] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Password Help</p>
        <h1 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Reset your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form class="flex flex-col gap-4" wire:submit="sendPasswordResetLink">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="mt-1 block w-full py-2.5" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-2 flex justify-end">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>

        <p class="mt-2 text-center text-sm text-slate-500">
            <a class="font-semibold text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('login') }}" wire:navigate>
                {{ __('Back to login') }}
            </a>
            @if (Route::has('register'))
                <span class="mx-2 text-slate-300">·</span>
                @if (config('registration.invite_only'))
                    <a class="font-semibold text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('register') }}" wire:navigate>
                        {{ __('Register with invite') }}
                    </a>
                @else
                    <a class="font-semibold text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('register') }}" wire:navigate>
                        {{ __('Register') }}
                    </a>
                @endif
            @endif
        </p>
    </form>
</div>
