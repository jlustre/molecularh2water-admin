<?php

namespace App\Livewire\Crm;

use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Crm\ConsultationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LeadConsultationsPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public bool $showForm = false;

    public string $customer_needs = '';

    public string $product_recommendation = '';

    public ?int $family_size = null;

    public string $water_consumption = '';

    public string $budget = '';

    public string $financing_option = '';

    public string $health_goals = '';

    public string $objections = '';

    public string $competitor_comparison = '';

    public string $final_recommendation = '';

    public string $conducted_at = '';

    public string $notes = '';

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->conducted_at = now()->format('Y-m-d\TH:i');
    }

    public function toggleForm(): void
    {
        $this->authorize('update', $this->lead);
        $this->showForm = ! $this->showForm;
    }

    public function saveConsultation(ConsultationService $consultations): void
    {
        $this->authorize('update', $this->lead);

        $validated = $this->validate([
            'customer_needs' => ['nullable', 'string', 'max:5000'],
            'product_recommendation' => ['nullable', 'string', 'max:2000'],
            'family_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'water_consumption' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'financing_option' => ['nullable', 'string', 'max:255'],
            'health_goals' => ['nullable', 'string', 'max:2000'],
            'objections' => ['nullable', 'string', 'max:2000'],
            'competitor_comparison' => ['nullable', 'string', 'max:2000'],
            'final_recommendation' => ['nullable', 'string', 'max:2000'],
            'conducted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $consultations->record($this->lead, $validated, auth()->user());

        $this->reset([
            'showForm', 'customer_needs', 'product_recommendation', 'family_size',
            'water_consumption', 'budget', 'financing_option', 'health_goals',
            'objections', 'competitor_comparison', 'final_recommendation', 'notes',
        ]);
        $this->conducted_at = now()->format('Y-m-d\TH:i');
        $this->lead->refresh();
    }

    public function render()
    {
        return view('livewire.crm.lead-consultations-panel', [
            'consultations' => $this->lead->consultations()->with('consultant')->limit(10)->get(),
        ]);
    }
}
