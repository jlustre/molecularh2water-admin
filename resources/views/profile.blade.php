@php
    use App\Support\Portal\ProfileAccountOverview;

    $user = auth()->user();
    $userName = $user?->name ?? 'Member';
    $userEmail = $user?->email ?? '';
    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = $initials !== '' ? mb_strtoupper($initials) : 'AU';
    $roles = $user?->roles()->orderBy('name')->get() ?? collect();
    $isVerified = filled($user?->email_verified_at);
    $canDeleteAccount = $user?->hasRole('super-admin') ?? false;
    $avatarUrl = $user?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) : null;
    $accountCards = $user ? ProfileAccountOverview::cards($user) : [];
@endphp

@extends('layouts.portal')

@section('content')
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl">
            <section class="overflow-hidden rounded-lg border border-teal-200/[0.18] bg-white/[0.07] shadow-2xl shadow-teal-950/20 backdrop-blur-xl">
                <div class="grid gap-0 lg:grid-cols-[0.78fr_1.22fr]">
                    <aside class="border-b border-teal-200/[0.14] bg-[#041f1e]/85 p-6 sm:p-8 lg:border-b-0 lg:border-r">
                        <div class="flex flex-col gap-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="relative"
                                    x-data="{ avatarUrl: @js($avatarUrl) }"
                                    x-on:profile-updated.window="avatarUrl = $event.detail.avatarUrl || avatarUrl"
                                >
                                    <img x-show="avatarUrl" :src="avatarUrl" alt="{{ $userName }} avatar" class="size-20 rounded-full border-2 border-teal-300/40 object-cover shadow-[0_0_32px_rgba(45,212,191,0.22)]">
                                    <span x-show="! avatarUrl" class="flex size-20 items-center justify-center rounded-full border-2 border-teal-300/40 bg-gradient-to-br from-teal-300/30 to-teal-700/30 text-2xl font-black text-white shadow-[0_0_32px_rgba(45,212,191,0.22)]">
                                        {{ $initials }}
                                    </span>
                                    <span class="absolute bottom-1 right-1 size-4 rounded-full border-2 border-[#041f1e] {{ $isVerified ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-200/65">Account Profile</p>
                                    <h2 class="mt-2 truncate text-3xl font-black tracking-normal text-white">{{ $userName }}</h2>
                                    <p class="mt-1 truncate text-sm font-semibold text-teal-100/65">{{ $userEmail }}</p>
                                </div>
                            </div>

                            <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Assigned Roles</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse ($roles as $role)
                                        <span class="rounded-full bg-teal-300/15 px-3 py-1 text-xs font-bold text-teal-100 ring-1 ring-teal-200/20">{{ $role->name }}</span>
                                    @empty
                                        <span class="rounded-full bg-slate-100/10 px-3 py-1 text-xs font-bold text-teal-100 ring-1 ring-teal-200/20">Member</span>
                                    @endforelse
                                </div>
                            </div>

                            @if ($accountCards !== [])
                                <x-portal.profile-account-summary :cards="$accountCards" />
                            @endif
                        </div>
                    </aside>

                    <div class="bg-slate-50 p-4 text-slate-900 sm:p-6 lg:p-8">
                        <section class="mb-6 rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Profile Center</p>
                            <h2 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Manage your account details and security.</h2>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                                Keep your contact details current, rotate your password regularly, and review the access roles connected to your account.
                            </p>
                        </section>

                        <div class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                            <div class="space-y-6">
                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                    <livewire:profile.update-profile-information-form />
                                </section>

                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                    <livewire:profile.update-password-form />
                                </section>
                            </div>

                            <aside class="space-y-6">
                                <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Security Notes</p>
                                    <div class="mt-5 space-y-4">
                                        <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3">
                                            <p class="text-sm font-bold text-slate-950">Use a unique password</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">Choose a password you do not use on any other account.</p>
                                        </div>
                                        <div class="rounded-md border border-teal-100 bg-white px-4 py-3">
                                            <p class="text-sm font-bold text-slate-950">Keep your email current</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">Email changes may require verification before account notifications resume.</p>
                                        </div>
                                        <div class="rounded-md border border-teal-100 bg-white px-4 py-3">
                                            <p class="text-sm font-bold text-slate-950">Review assigned roles</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">Ask a super admin to adjust your access if something looks incorrect.</p>
                                        </div>
                                    </div>
                                </section>

                                @if ($canDeleteAccount)
                                    <section class="rounded-lg border border-red-100 bg-white p-6 shadow-sm">
                                        <livewire:profile.delete-user-form />
                                    </section>
                                @endif
                            </aside>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
