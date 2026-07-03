<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split', [
    'badge' => 'Secure Access',
    'heading' => 'One calm control point for your admin work.',
    'subtext' => 'Sign in to manage leads, appointments, content, customer messages, and system settings in the same teal operations environment.',
])] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Admin Access</p>
        <h1 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Welcome back</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Log in to continue managing the Molecular H2 Water portal.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form class="flex flex-col gap-4" wire:submit="login">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-1 block w-full py-2.5" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                wire:model="form.password"
                id="password"
                class="mt-1 block w-full py-2.5"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-teal-200 text-teal-600 shadow-sm focus:ring-teal-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="mt-2 flex justify-end">
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="mt-2 text-center text-sm text-slate-500">
                @if (config('registration.invite_only'))
                    {{ __('Registration is invite-only.') }}
                    <span class="block mt-1">Use the one-time link or code from your sponsor.</span>
                @else
                    {{ __('Need an account?') }}
                    <a class="font-semibold text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('register') }}" wire:navigate>
                        {{ __('Register') }}
                    </a>
                @endif
            </p>
        @endif
    </form>
</div>
