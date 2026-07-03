<div class="space-y-4">
  @can('update', $lead)
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Quick Log Activity</h3>
      <form class="mt-3 space-y-3" wire:submit="logActivity">
        <select class="w-full rounded-xl border-slate-200 text-sm shadow-sm" wire:model="activity_type_id">
          <option value="">Activity type</option>
          @foreach ($types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
        @error('activity_type_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        <textarea class="w-full rounded-xl border-slate-200 text-sm shadow-sm" placeholder="What happened?" rows="2" wire:model="activity_description"></textarea>
        <select class="w-full rounded-xl border-slate-200 text-sm shadow-sm" wire:model="activity_outcome">
          <option value="">Outcome (optional)</option>
          @foreach ($outcomes as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
          @endforeach
        </select>
        <button class="rounded-full bg-teal-600 px-4 py-2 text-xs font-semibold text-white" type="submit">Log Activity</button>
      </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Quick Add Task</h3>
      <form class="mt-3 space-y-3" wire:submit="addTask">
        <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm" placeholder="Task title" type="text" wire:model="task_title" />
        @error('task_title') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm" type="datetime-local" wire:model="task_due_at" />
        <button class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white" type="submit">Add Task</button>
      </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Quick Schedule</h3>
      <form class="mt-3 space-y-3" wire:submit="scheduleAppointment">
        <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm" placeholder="Appointment title" type="text" wire:model="appointment_title" />
        @error('appointment_title') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm" type="datetime-local" wire:model="appointment_starts_at" />
        <button class="rounded-full bg-cyan-700 px-4 py-2 text-xs font-semibold text-white" type="submit">Schedule</button>
      </form>
    </div>
  @endcan
</div>
