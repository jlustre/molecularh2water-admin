<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Crm\CrmPermissions;
use App\Services\RegistrationInviteService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split', [
    'badge' => 'Build Your Business',
    'heading' => 'Manage your H2 business in one place.',
    'subtext' => 'Leads, demos, quotes, orders, and growth tools — ready when you join.',
    'portalLabel' => 'Member Portal',
])] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $inviteCode = '';

    public bool $inviteAccepted = false;

    public ?string $sponsorName = null;

    public function mount(): void
    {
        $invites = app(RegistrationInviteService::class);
        $code = request()->route('code') ?? request()->query('code');
        $this->inviteCode = $invites->normalizeCode(is_string($code) ? $code : '');

        if (! config('registration.invite_only')) {
            $this->inviteAccepted = true;

            return;
        }

        $invite = $invites->findValidInvite($this->inviteCode);

        if ($invite) {
            $this->inviteAccepted = true;
            $this->sponsorName = $invite->sponsor?->name;
        }
    }

    public function verifyInvite(RegistrationInviteService $invites): void
    {
        $this->inviteCode = $invites->normalizeCode($this->inviteCode);
        $invite = $invites->findValidInvite($this->inviteCode);

        if (! $invite) {
            throw ValidationException::withMessages([
                'inviteCode' => 'This invite code is invalid, expired, or has already been used.',
            ]);
        }

        $this->inviteAccepted = true;
        $this->sponsorName = $invite->sponsor?->name;
    }

    public function register(RegistrationInviteService $invites): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = DB::transaction(function () use ($invites, $validated) {
            if (config('registration.invite_only')) {
                $invite = $invites->findValidInvite($this->inviteCode);

                if (! $invite) {
                    throw ValidationException::withMessages([
                        'inviteCode' => 'This invite code is no longer valid.',
                    ]);
                }

                $validated['sponsor_id'] = $invite->sponsor_id;
            }

            $user = User::create($validated);

            if (config('registration.invite_only')) {
                $invites->consume($invite, $user);
            }

            return $user;
        });

        event(new Registered($user));

        $memberRole = Role::query()->firstOrCreate(
            ['slug' => 'member'],
            [
                'name' => 'Member',
                'description' => 'Registered portal member with resources, sponsor tools, and field CRM access.',
                'status' => 'active',
                'color' => 'slate',
                'permissions' => CrmPermissions::defaultsByRole()['member'],
                'is_system' => true,
            ],
        );

        $user->roles()->syncWithoutDetaching([$memberRole->id]);

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">New Member</p>
        <h1 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Create your account</h1>
        @if ($inviteAccepted && $sponsorName)
            <p class="mt-2 text-sm leading-6 text-slate-500">
                You were invited by <span class="font-semibold text-slate-800">{{ $sponsorName }}</span>.
                Complete the form below to join the Molecular H2 Water portal.
            </p>
            <p class="mt-3 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2.5 text-sm leading-6 text-teal-900">
                If this is not your sponsor, do not register with this link. Ask your correct sponsor for their personal invite link first.
            </p>
        @elseif ($inviteAccepted)
            <p class="mt-2 text-sm leading-6 text-slate-500">Set up member access for the Molecular H2 Water portal.</p>
        @else
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Registration is invite-only. Ask your sponsor for a personal registration link or code.
            </p>
        @endif
    </div>

    @if (! $inviteAccepted)
        <div class="rounded-xl border-2 border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            <p class="font-semibold">Invite required</p>
            <p class="mt-2 leading-6">
                Open the one-time registration link your sponsor sent you, or paste your invite code on this page.
            </p>
        </div>

        <form class="mt-6" wire:submit="verifyInvite">
            <div>
                <x-input-label for="inviteCode" value="Invite code" />
                <x-text-input
                    wire:model="inviteCode"
                    id="inviteCode"
                    class="mt-1 block w-full font-mono uppercase tracking-wider"
                    type="text"
                    name="inviteCode"
                    placeholder="XXXX-XXXX"
                    autocomplete="off"
                />
                <x-input-error :messages="$errors->get('inviteCode')" class="mt-2" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <x-primary-button>
                    Verify code
                </x-primary-button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Already have an account?
            <a class="font-semibold text-teal-700 underline decoration-teal-300 underline-offset-4" href="{{ route('login') }}" wire:navigate>Sign in</a>
        </p>
    @else
        <form class="flex flex-col gap-4" wire:submit="register">
            @if (config('registration.invite_only'))
                <input type="hidden" wire:model="inviteCode" />
            @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" class="mt-1 block w-full py-2.5" type="text" name="name" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input wire:model="email" id="email" class="mt-1 block w-full py-2.5" type="email" name="email" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input wire:model="password" id="password" class="mt-1 block w-full py-2.5" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full py-2.5" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="mt-2 flex items-center justify-between gap-4">
                <a class="rounded-md text-sm font-medium text-teal-700 underline decoration-teal-300 underline-offset-4 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" href="{{ route('login') }}" wire:navigate>
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button>
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</div>
