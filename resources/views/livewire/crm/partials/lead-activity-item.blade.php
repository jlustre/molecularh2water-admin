@props(['activity', 'canDelete' => false])

<article
    {{ $attributes->class(['rounded-lg border border-slate-200 bg-white p-3 shadow-sm']) }}
    wire:key="activity-{{ $activity->id }}"
>
  <div class="flex items-start justify-between gap-3">
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-teal-800">
          {{ $activity->type?->name ?? 'Activity' }}
        </span>
        @if ($activity->outcome)
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
            {{ config('crm.activity_outcomes.'.$activity->outcome, $activity->outcome) }}
          </span>
        @endif
      </div>
      <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ $activity->title }}</h4>
      @if ($activity->description)
        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $activity->description }}</p>
      @endif
      @if ($activity->next_action)
        <p class="mt-2 text-xs text-slate-500">
          <span class="font-semibold text-slate-600">Next:</span> {{ $activity->next_action }}
        </p>
      @endif
    </div>
    @if ($canDelete)
      <button
        class="shrink-0 text-xs font-semibold text-rose-600 hover:text-rose-800"
        type="button"
        wire:click="deleteActivity({{ $activity->id }})"
        wire:confirm="Remove this activity?"
      >
        Delete
      </button>
    @endif
  </div>
  <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
    <span>{{ $activity->completed_at?->format('M j, Y g:i A') ?? '—' }}</span>
    @if ($activity->duration_minutes)
      <span>{{ $activity->duration_minutes }} min</span>
    @endif
    <span>{{ $activity->user?->name ?? 'System' }}</span>
  </div>
</article>
