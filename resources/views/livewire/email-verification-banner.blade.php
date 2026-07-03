<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $linkSent = false;

    public function mount(): void
    {
        $this->linkSent = session('verification-link-sent', false) === true
            || session('status') === 'verification-link-sent';
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('verification-link-sent', true);
        $this->linkSent = true;
    }
}; ?>

@if (auth()->user() && ! auth()->user()->hasVerifiedEmail())
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-amber-950 shadow-sm sm:px-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-bold text-amber-950">{{ __('Email verification required') }}</p>
                <p class="mt-1 text-sm leading-6 text-amber-900/90">
                    {{ __('Your email address is not verified yet. Check your inbox for the verification link, or resend it below to unlock full portal access.') }}
                </p>

                @if ($linkSent)
                    <p class="mt-2 text-sm font-semibold text-emerald-700">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>

            <button
                type="button"
                wire:click="sendVerification"
                wire:loading.attr="disabled"
                class="shrink-0 rounded-md text-sm font-bold text-teal-800 underline underline-offset-4 transition hover:text-teal-950 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="sendVerification">{{ __('Resend verification email') }}</span>
                <span wire:loading wire:target="sendVerification">{{ __('Sending...') }}</span>
            </button>
        </div>
    </div>
@endif
