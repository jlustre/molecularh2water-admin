<div>
  <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
    <div>
      <h2 class="text-sm font-bold text-slate-900">Activities</h2>
      <p class="text-[11px] text-slate-500">Calls, demos, follow-ups, and other touchpoints for this prospect.</p>
    </div>
    @if ($canLog)
      <button
        class="shrink-0 rounded-full {{ $showLogForm ? 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'bg-teal-600 text-white hover:bg-teal-700' }} px-4 py-1.5 text-xs font-semibold shadow-sm"
        type="button"
        wire:click="toggleLogForm"
      >
        {{ $showLogForm ? 'Close' : 'Log Activity' }}
      </button>
    @endif
  </div>

  <div class="space-y-4 bg-slate-100 p-4">
    @if ($showLogForm && $canLog)
      <div class="rounded-lg border border-teal-200 bg-white p-4 shadow-sm">
        @include('livewire.crm.partials.lead-activities-log-form')
      </div>
    @endif

    @stack('lead-activities-before-list')

    <div class="space-y-2">
      @forelse ($activities as $activity)
        @include('livewire.crm.partials.lead-activity-item', [
            'activity' => $activity,
            'canDelete' => $canLog,
        ])
      @empty
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
          <p class="text-sm font-medium text-slate-700">No activities logged yet</p>
          <p class="mt-1 text-xs text-slate-500">Record your first call, demo, or follow-up to build this prospect's history.</p>
          @if ($canLog && ! $showLogForm)
            <button
              class="mt-3 rounded-full bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-teal-700"
              type="button"
              wire:click="toggleLogForm"
            >
              Log Activity
            </button>
          @endif
        </div>
      @endforelse
    </div>

    @if ($activities->hasPages())
      <div class="pt-1">
        {{ $activities->links() }}
      </div>
    @endif

    @stack('lead-activities-after-list')
  </div>
</div>
