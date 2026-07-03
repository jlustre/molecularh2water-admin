<form class="space-y-3" wire:submit="logActivity">
  <div class="grid gap-3 sm:grid-cols-2">
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Activity Type *</label>
      <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="activity_type_id">
        <option value="">Select type...</option>
        @foreach ($types as $type)
          <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
      </select>
      @error('activity_type_id') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Completed At</label>
      <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="datetime-local" wire:model="completed_at" />
      @error('completed_at') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Title</label>
      <input
        class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
        placeholder="Optional — defaults to activity type"
        type="text"
        wire:model="title"
      />
    </div>
    <div class="sm:col-span-2">
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Description</label>
      <textarea
        class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
        placeholder="What happened on this touchpoint?"
        rows="3"
        wire:model="description"
      ></textarea>
      @error('description') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Outcome</label>
      <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="outcome">
        <option value="">—</option>
        @foreach ($outcomes as $value => $label)
          <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Duration (minutes)</label>
      <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" min="1" type="number" wire:model="duration_minutes" />
    </div>
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Next Action</label>
      <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="next_action" />
    </div>
    <div>
      <label class="mb-0.5 block text-xs font-semibold text-slate-600">Next Follow-Up</label>
      <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="datetime-local" wire:model="next_follow_up_at" />
    </div>
  </div>

  @stack('lead-activities-log-form-fields')

  <div class="flex justify-end gap-2 pt-1">
    <button
      class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
      type="button"
      wire:click="toggleLogForm"
    >
      Cancel
    </button>
    <button
      class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
      type="submit"
    >
      Save Activity
    </button>
  </div>
</form>
