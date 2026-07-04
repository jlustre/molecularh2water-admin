@php
    $flashMessage = session('invite_status') ?? session('status');
@endphp

<div class="relative">
@if ($flashMessage)
    <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
        {{ $flashMessage }}
    </div>
@endif

@if (empty($compact))
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Sponsor</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">Member registration invites</h2>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
            Generate one-time registration links for people you sponsor. Each code can only be used once.
        </p>
    </div>
@endif

@if ($generatedUrl)
    <div class="mb-6 rounded-2xl border border-teal-200 bg-teal-50 p-5">
        <p class="text-sm font-semibold text-teal-900">New invite ready</p>
        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Registration code</p>
        <p class="mt-1 font-mono text-lg font-bold tracking-wider text-slate-900">{{ $generatedCode }}</p>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-teal-700">Invite link</p>
        <p class="mt-1 break-all text-sm text-slate-800">{{ $generatedUrl }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button
                class="inline-flex items-center justify-center rounded-full border border-teal-300 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-100"
                type="button"
                wire:click="openEmailModal"
            >
                Email invite
            </button>
            <button
                class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                type="button"
                x-data="{ copied: false }"
                x-on:click="navigator.clipboard.writeText(@js($generatedUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            >
                <span x-show="!copied">Copy link</span>
                <span x-cloak x-show="copied">Copied!</span>
            </button>
        </div>
        <p class="mt-3 text-xs text-teal-800">Share the link or code with your invitee. It expires after {{ config('registration.invite_ttl_days') }} days.</p>
    </div>
@endif

<div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-bold text-slate-900">Generate invite</h3>
    <form class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end" wire:submit="generateInvite">
        <div class="flex-1">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="invite-label">Label (optional)</label>
            <input
                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                id="invite-label"
                placeholder="e.g. June team prospect"
                type="text"
                wire:model="label"
            />
            @error('label') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <button class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm" type="submit">
            Create invite
        </button>
    </form>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="text-lg font-bold text-slate-900">Your invites</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Label</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Member</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($invites as $invite)
                    @php($inviteUrl = route('register.invite', ['code' => $invite->code]))
                    <tr>
                        <td class="px-4 py-3 font-mono text-sm font-semibold text-slate-800">{{ $invite->code }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $invite->label ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($invite->isConsumed())
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Used</span>
                            @elseif ($invite->isExpired())
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Expired</span>
                            @else
                                <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800">Available</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if ($invite->registeredUser)
                                {{ $invite->registeredUser->name }}
                                <span class="block text-xs text-slate-400">{{ $invite->registeredUser->email }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $invite->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($invite->isAvailable())
                                <div class="inline-flex flex-wrap justify-end gap-2">
                                    <button
                                        class="inline-flex items-center justify-center rounded-full border border-teal-300 bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 transition hover:bg-teal-50"
                                        type="button"
                                        wire:click="openEmailModal({{ $invite->id }})"
                                    >
                                        Email
                                    </button>
                                    <button
                                        class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        type="button"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText(@js($inviteUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    >
                                        <span x-show="!copied">Copy link</span>
                                        <span x-cloak x-show="copied">Copied!</span>
                                    </button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-sm text-slate-500" colspan="6">
                            No invites yet. Create one to sponsor a new member.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-4 py-3">
        {{ $invites->links() }}
    </div>
</div>

@if ($showEmailModal)
    <div class="shell-modal-overlay-nested fixed inset-0 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold text-slate-900">Email registration invite</h3>
            <p class="mt-1 text-sm text-slate-600">
                Send the invite link to your prospect. They will receive your name and a one-time registration link.
            </p>

            <form class="mt-5 space-y-4" wire:submit="sendInviteEmail">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="recipientEmail">Recipient email</label>
                    <input
                        class="w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        id="recipientEmail"
                        placeholder="prospect@example.com"
                        type="email"
                        wire:model="recipientEmail"
                    />
                    @error('recipientEmail') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="emailMessage">Personal message (optional)</label>
                    <textarea
                        class="w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        id="emailMessage"
                        placeholder="Looking forward to having you on the team!"
                        rows="3"
                        wire:model="emailMessage"
                    ></textarea>
                    @error('emailMessage') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap justify-end gap-2 pt-2">
                    <button
                        class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        type="button"
                        wire:click="closeEmailModal"
                    >
                        Cancel
                    </button>
                    <button
                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm"
                        type="submit"
                    >
                        Send email
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
