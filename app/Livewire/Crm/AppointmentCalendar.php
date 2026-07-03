<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\AppointmentStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Appointment;
use App\Models\Crm\Lead;
use App\Services\Crm\AppointmentService;
use App\Support\Crm\CrmScope;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AppointmentCalendar extends Component
{
    use AuthorizesRequests;
    use UsesCrmLayout;

    public string $month;

    public bool $showForm = false;

    public ?int $editingAppointmentId = null;

    public ?int $lead_id = null;

    public string $title = '';

    public string $meeting_type = 'home_demo';

    public string $location = '';

    public string $zoom_link = '';

    public string $status = 'scheduled';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $reminder_notes = '';

    public function mount(?int $lead = null): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.view'), 403);

        $this->month = now()->format('Y-m');

        if ($lead) {
            $this->lead_id = $lead;
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
    }

    public function openForm(?int $appointmentId = null): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        if ($appointmentId) {
            $appointment = CrmScope::appointments(Appointment::query())->findOrFail($appointmentId);
            $this->editingAppointmentId = $appointment->id;
            $this->lead_id = $appointment->lead_id;
            $this->title = $appointment->title;
            $this->meeting_type = $appointment->meeting_type ?? 'home_demo';
            $this->location = $appointment->location ?? '';
            $this->zoom_link = $appointment->zoom_link ?? '';
            $this->status = $appointment->status?->value ?? 'scheduled';
            $this->starts_at = $appointment->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->ends_at = $appointment->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->reminder_notes = $appointment->reminder_notes ?? '';
        } else {
            $this->resetForm();
            $this->starts_at = now()->addHour()->startOfHour()->format('Y-m-d\TH:i');
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(AppointmentService $appointmentService): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        $data = $this->validate($this->rules());

        if (! empty($data['lead_id'])) {
            $lead = CrmScope::leads(Lead::query())->findOrFail($data['lead_id']);
            $this->authorize('view', $lead);
        }

        $payload = array_merge($data, [
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?: null,
        ]);

        if ($this->editingAppointmentId) {
            $appointment = CrmScope::appointments(Appointment::query())->findOrFail($this->editingAppointmentId);
            $appointmentService->update($appointment, $payload, auth()->user());
            session()->flash('status', 'Appointment updated.');
        } else {
            $appointmentService->create($payload, auth()->user());
            session()->flash('status', 'Appointment scheduled.');
        }

        $this->closeForm();
    }

    public function cancelAppointment(int $appointmentId, AppointmentService $appointmentService): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        $appointment = CrmScope::appointments(Appointment::query())->findOrFail($appointmentId);
        $appointmentService->cancel($appointment, auth()->user());
        session()->flash('status', 'Appointment cancelled.');
    }

    public function deleteAppointment(int $appointmentId, AppointmentService $appointmentService): void
    {
        abort_unless(auth()->user()?->hasPermission('appointments.manage'), 403);

        $appointment = CrmScope::appointments(Appointment::query())->findOrFail($appointmentId);
        $appointmentService->delete($appointment, auth()->user());
        session()->flash('status', 'Appointment deleted.');
    }

    public function render()
    {
        $monthStart = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $appointments = CrmScope::appointments(Appointment::query())
            ->with(['lead', 'user'])
            ->whereBetween('starts_at', [$monthStart, $monthEnd])
            ->when($this->lead_id, fn ($q) => $q->whereLeadId($this->lead_id))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'));

        return view('livewire.crm.appointment-calendar', [
            'monthLabel' => $monthStart->format('F Y'),
            'monthStart' => $monthStart,
            'appointments' => $appointments,
            'leads' => CrmScope::leads(Lead::query())->orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'email']),
            'meetingTypes' => config('crm.meeting_types', []),
            'statuses' => AppointmentStatus::cases(),
        ])->layout($this->crmLayout());
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'lead_id' => ['nullable', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:255'],
            'meeting_type' => ['nullable', Rule::in(array_keys(config('crm.meeting_types', [])))],
            'location' => ['nullable', 'string', 'max:255'],
            'zoom_link' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'reminder_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingAppointmentId',
            'title',
            'location',
            'zoom_link',
            'starts_at',
            'ends_at',
            'reminder_notes',
        ]);
        $this->meeting_type = 'home_demo';
        $this->status = 'scheduled';
    }
}
