<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar = null;
    public ?string $avatarUrl = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->avatarUrl = Auth::user()->avatar_path
            ? Storage::disk('public')->url(Auth::user()->avatar_path)
            : null;
    }

    public function updatedAvatar(): void
    {
        $this->validateOnly('avatar', [
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->avatar) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $this->avatar->store('avatars', 'public');
        }

        $user->save();

        $this->avatarUrl = $user->avatar_path
            ? Storage::disk('public')->url($user->avatar_path)
            : null;
        $this->reset('avatar');

        $this->dispatch('profile-updated', name: $user->name, avatarUrl: $this->avatarUrl);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">
            {{ __('Identity') }}
        </p>

        <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">
            {{ __('Profile information') }}
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 grid gap-5">
        <div
            class="rounded-lg border border-teal-100 bg-teal-50/70 p-4"
            x-data="{
                previewUrl: @js($avatarUrl),
                updatePreview(event) {
                    const file = event.target.files[0];

                    if (! file) {
                        return;
                    }

                    if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(this.previewUrl);
                    }

                    this.previewUrl = URL.createObjectURL(file);
                },
            }"
            x-on:profile-updated.window="
                if (previewUrl && previewUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(previewUrl);
                }

                previewUrl = $event.detail.avatarUrl || previewUrl;
            "
        >
            <p class="text-sm font-bold text-slate-950">{{ __('Avatar') }}</p>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="relative size-24 overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-teal-300 to-teal-700 shadow-md">
                    <img x-show="previewUrl" :src="previewUrl" alt="{{ __('Avatar preview') }}" class="size-full object-cover">
                    <span x-show="! previewUrl" class="flex size-full items-center justify-center text-2xl font-black text-white">
                        {{ collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') ?: 'AU' }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <x-input-label for="avatar" :value="__('Upload or replace avatar')" />
                    <input wire:model="avatar" x-on:change="updatePreview($event)" id="avatar" name="avatar" type="file" accept="image/*" class="mt-2 block w-full rounded-md border border-teal-100 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:bg-teal-700 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-teal-800 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600" />
                    <p class="mt-2 text-xs font-medium text-slate-500">{{ __('PNG, JPG, or WEBP up to 2 MB. Preview updates before you save.') }}</p>
                    <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                    <div wire:loading wire:target="avatar" class="mt-2 text-xs font-bold text-teal-700">{{ __('Preparing preview...') }}</div>
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-2 block w-full border-teal-100 focus:border-teal-600 focus:ring-teal-600" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-2 block w-full border-teal-100 focus:border-teal-600 focus:ring-teal-600" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-sm leading-6 text-amber-900">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="rounded-md text-sm font-bold text-teal-800 underline underline-offset-4 hover:text-teal-950 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-bold text-emerald-700">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4 border-t border-teal-50 pt-2">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3 text-sm font-bold text-emerald-700" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
