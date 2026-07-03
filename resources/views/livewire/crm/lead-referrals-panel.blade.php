<div>
    @if ($showPanel)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-blue-50 to-slate-100 px-4 py-2.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Referrals</h2>
                        <p class="text-[11px] text-slate-500">Log referred contacts as leads and track rewards.</p>
                    </div>
                    @can('update', $lead)
                        <button
                            class="rounded-full bg-blue-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                            type="button"
                            wire:click="toggleForm"
                        >
                            {{ $showForm ? 'Cancel' : 'Log Referral' }}
                        </button>
                    @endcan
                </div>
            </div>

            <div class="space-y-4 bg-slate-100 p-4">
                @if ($showForm)
                    <form class="grid gap-3 rounded-lg border border-white bg-white p-4 shadow-sm sm:grid-cols-2" wire:submit="saveReferral">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First Name</label>
                            <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="first_name" />
                            @error('first_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last Name</label>
                            <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="last_name" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                            <input class="w-full rounded-lg border-slate-200 text-sm" type="email" wire:model="email" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
                            <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="phone" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                            <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="notes"></textarea>
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button class="rounded-full bg-marine px-5 py-2 text-sm font-bold text-white hover:bg-blue-700" type="submit">
                                Save Referral
                            </button>
                        </div>
                    </form>
                @endif

                <div class="space-y-3">
                    @forelse ($referrals as $referral)
                        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <a class="text-sm font-bold text-blue-800 hover:text-blue-900" href="{{ $this->referredProfileUrl($referral->referred) }}">
                                        {{ $referral->referred->fullName() }}
                                    </a>
                                    <p class="mt-1 text-xs text-slate-500">{{ $referral->created_at->format('M j, Y') }} · {{ $referral->referred->stage?->name ?? 'No stage' }}</p>
                                </div>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-800">
                                    {{ $referral->status->label() }}
                                </span>
                            </div>

                            @if ($referral->reward_type && $referral->status->value === 'rewarded')
                                <p class="mt-2 text-xs text-emerald-700">
                                    Reward: {{ $rewardTypes[$referral->reward_type] ?? $referral->reward_type }}
                                    @if ($referral->reward_amount)
                                        — ${{ number_format($referral->reward_amount, 2) }}
                                    @endif
                                </p>
                            @endif

                            @can('update', $lead)
                                @if (in_array($referral->status->value, ['converted', 'pending', 'contacted'], true) && $rewardingReferralId !== $referral->id)
                                    <button
                                        class="mt-3 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100"
                                        type="button"
                                        wire:click="startReward({{ $referral->id }})"
                                    >
                                        Issue Reward
                                    </button>
                                @endif
                            @endcan

                            @if ($rewardingReferralId === $referral->id)
                                <form class="mt-3 grid gap-3 rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 sm:grid-cols-2" wire:submit="issueReward">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Reward Type</label>
                                        <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="reward_type">
                                            @foreach ($rewardTypes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Amount</label>
                                        <input class="w-full rounded-lg border-slate-200 text-sm" min="0" step="0.01" type="number" wire:model="reward_amount" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                                        <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="reward_notes"></textarea>
                                    </div>
                                    <div class="sm:col-span-2 flex justify-end gap-2">
                                        <button class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600" type="button" wire:click="$set('rewardingReferralId', null)">Cancel</button>
                                        <button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700" type="submit">Save Reward</button>
                                    </div>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                            No referrals logged yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
