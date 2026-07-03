<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\LeadSource;
use App\Models\Crm\LostReason;
use App\Models\Crm\Tag;
use App\Models\User;
use App\Services\Crm\LeadService;
use App\Support\BusinessLineContext;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LeadForm extends Component
{
    use UsesCrmLayout;

    public Lead|Prospect|Customer|Recruit|null $lead = null;

    public LeadLifecycle $lifecycle = LeadLifecycle::Lead;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $country = '';

    public string $company = '';

    public string $occupation = '';

    public string $spouse_name = '';

    public string $spouse_occupation = '';

    public string $best_time_to_contact = '';

    public string $status = 'new';

    public string $temperature = 'cold';

    public int $score = 0;

    public ?int $lead_source_id = null;

    public ?int $funnel_id = null;

    public ?int $funnel_stage_id = null;

    public ?int $assigned_user_id = null;

    public string $business_line = '';

    public string $interested_in = '';

    public string $message = '';

    public ?int $lost_reason_id = null;

    public string $lost_reason_detail = '';

    public string $last_contacted_at = '';

    public string $next_follow_up_at = '';

    public bool $consent_given = false;

    /** @var list<int> */
    public array $selectedTags = [];

    public function mount(Lead|Prospect|Customer|Recruit|null $lead = null): void
    {
        if ($lead) {
            $this->authorize('update', $lead);
            $this->lead = $lead;
            $this->lifecycle = $lead->lifecycle;
            $this->fillFromLead($lead);
        } else {
            $this->lifecycle = $this->resolveLifecycleFromRoute();
            $this->authorize('createForLifecycle', [Lead::class, $this->lifecycle]);

            if (! CrmScope::userCanViewAll(auth()->user())) {
                $this->assigned_user_id = auth()->id();
            }

            $this->business_line = \App\Support\BusinessLineResolver::defaultForUser(auth()->user());
            $this->funnel_id = $this->resolveDefaultFunnelId();
            $this->funnel_stage_id = $this->defaultStageIdForFunnel($this->funnel_id);
        }
    }

    public function updatedFunnelId(): void
    {
        if (! $this->funnel_id) {
            $this->funnel_stage_id = null;

            return;
        }

        $stageBelongsToFunnel = $this->funnel_stage_id
            && FunnelStage::query()
                ->whereKey($this->funnel_stage_id)
                ->where('funnel_id', $this->funnel_id)
                ->exists();

        if (! $stageBelongsToFunnel) {
            $this->funnel_stage_id = $this->defaultStageIdForFunnel($this->funnel_id);
        }
    }

    public function save(LeadService $leadService): void
    {
        $data = $this->validate($this->rules());

        $payload = array_merge($data, [
            'lifecycle' => $this->lifecycle->value,
            'last_contacted_at' => $this->last_contacted_at ?: null,
            'next_follow_up_at' => $this->next_follow_up_at ?: null,
        ]);

        $user = auth()->user();

        if ($this->lead) {
            $leadService->update($this->lead, $payload, $user, $this->selectedTags);
            session()->flash('status', ucfirst($this->lifecycle->value).' updated successfully.');

            $this->redirect($this->showRoute($this->lead), navigate: true);

            return;
        }

        $lead = $leadService->create($payload, $user, $this->selectedTags);
        session()->flash('status', ucfirst($this->lifecycle->value).' created successfully.');

        $this->redirect($this->showRoute($lead), navigate: true);
    }

    public function render()
    {
        return view('livewire.crm.lead-form', [
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'funnels' => Funnel::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'stages' => FunnelStage::query()
                ->when($this->funnel_id, fn ($query) => $query->where('funnel_id', $this->funnel_id))
                ->orderBy('sort_order')
                ->get(),
            'tags' => $this->tagsForForm(),
            'assignableUsers' => CrmScope::userCanViewAll(auth()->user())
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'businessLines' => BusinessLineContext::optionsForLeadForm(),
            'showBusinessLinePicker' => BusinessLineContext::showSwitcher(),
            'statuses' => LeadStatus::options(),
            'lostReasons' => LostReason::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'stageIsLost' => $this->selectedStageIsLost(),
            'isProspectForm' => $this->lifecycle === LeadLifecycle::Prospect,
            'bestTimesToContact' => config('crm.prospect_best_times_to_contact', []),
        ])->layout($this->crmLayout());
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'spouse_occupation' => ['nullable', 'string', 'max:255'],
            'best_time_to_contact' => [
                'nullable',
                Rule::in(array_keys(config('crm.prospect_best_times_to_contact', []))),
            ],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'temperature' => ['required', Rule::in(['cold', 'warm', 'hot'])],
            'score' => ['integer', 'min:0', 'max:100'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'funnel_id' => ['nullable', 'exists:funnels,id'],
            'funnel_stage_id' => ['nullable', 'exists:funnel_stages,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'business_line' => [
                'required',
                Rule::in(array_map(fn ($line) => $line->value, BusinessLineContext::optionsForLeadForm())),
            ],
            'interested_in' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'lost_reason_id' => [
                Rule::requiredIf(fn () => $this->selectedStageIsLost()),
                'nullable',
                'exists:lost_reasons,id',
            ],
            'lost_reason_detail' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(
                    fn () => $this->selectedStageIsLost()
                        && LostReason::query()->find($this->lost_reason_id)?->requires_detail,
                ),
            ],
            'consent_given' => ['boolean'],
            'selectedTags' => ['array'],
            'selectedTags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    private function fillFromLead(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->lifecycle = $lead->lifecycle;
        $this->first_name = $lead->first_name;
        $this->last_name = $lead->last_name ?? '';
        $this->email = $lead->email ?? '';
        $this->phone = $lead->phone ?? '';
        $this->address = $lead->address ?? '';
        $this->city = $lead->city ?? '';
        $this->state = $lead->state ?? '';
        $this->country = $lead->country ?? '';
        $this->company = $lead->company ?? '';
        $this->occupation = $lead->occupation ?? '';
        $this->spouse_name = $lead->spouse_name ?? '';
        $this->spouse_occupation = $lead->spouse_occupation ?? '';
        $this->best_time_to_contact = $lead->best_time_to_contact ?? '';
        $this->status = $lead->status?->value ?? LeadStatus::New->value;
        $this->temperature = $lead->temperature->value;
        $this->score = $lead->score;
        $this->lead_source_id = $lead->lead_source_id;
        $this->funnel_id = $lead->funnel_id;
        $this->funnel_stage_id = $lead->funnel_stage_id;
        $this->assigned_user_id = $lead->assigned_user_id;
        $this->business_line = $lead->business_line?->value ?? 'h2s';
        $this->interested_in = $lead->interested_in ?? '';
        $this->message = $lead->message ?? '';
        $this->lost_reason_id = $lead->lost_reason_id;
        if ($lead->lostReason?->requires_detail) {
            $this->lost_reason_detail = $lead->lost_reason ?? '';
        } else {
            $this->lost_reason_detail = '';
        }
        $this->last_contacted_at = $lead->last_contacted_at?->format('Y-m-d\TH:i') ?? '';
        $this->next_follow_up_at = $lead->next_follow_up_at?->format('Y-m-d\TH:i') ?? '';
        $this->consent_given = $lead->consent_given;
        $this->selectedTags = $lead->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
    }

    private function resolveLifecycleFromRoute(): LeadLifecycle
    {
        $routeName = request()->route()?->getName() ?? '';

        return match (true) {
            str_contains($routeName, 'prospects') => LeadLifecycle::Prospect,
            str_contains($routeName, 'customers') => LeadLifecycle::Client,
            str_contains($routeName, 'recruits') => LeadLifecycle::Recruit,
            default => LeadLifecycle::Lead,
        };
    }

    public function profileUrl(): string
    {
        return $this->showRoute($this->lead);
    }

    public function listUrl(): string
    {
        return match ($this->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.index'),
            LeadLifecycle::Client => CrmRoutes::url('customers.index'),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.index'),
            default => CrmRoutes::url('leads.index'),
        };
    }

    private function tagsForForm()
    {
        if ($this->lifecycle !== LeadLifecycle::Prospect) {
            return Tag::query()->orderBy('name')->get();
        }

        foreach (config('crm.prospect_profile_tags', []) as $name) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        $slugs = collect(config('crm.prospect_profile_tags', []))
            ->map(fn (string $name) => Str::slug($name));

        return Tag::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->sortBy(fn (Tag $tag) => $slugs->search($tag->slug) ?? 999)
            ->values();
    }

    private function selectedStageIsLost(): bool
    {
        if (! $this->funnel_stage_id) {
            return false;
        }

        return FunnelStage::query()->find($this->funnel_stage_id)?->is_lost ?? false;
    }

    private function showRoute(Lead|Prospect|Customer|Recruit $lead): string
    {
        return match ($lead->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.show', ['lead' => $lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.show', ['lead' => $lead]),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.show', ['lead' => $lead]),
            default => CrmRoutes::url('leads.show', ['lead' => $lead]),
        };
    }

    private function resolveDefaultFunnelId(): ?int
    {
        $slug = match ($this->lifecycle) {
            LeadLifecycle::Recruit => 'recruiting-funnel',
            default => config('crm.default_funnel_slug', 'sales-funnel'),
        };

        return Funnel::query()->where('slug', $slug)->where('is_active', true)->value('id')
            ?? Funnel::query()->where('is_default', true)->where('is_active', true)->value('id');
    }

    private function defaultStageIdForFunnel(?int $funnelId): ?int
    {
        if (! $funnelId) {
            return null;
        }

        return FunnelStage::query()
            ->where('funnel_id', $funnelId)
            ->orderBy('sort_order')
            ->value('id');
    }
}
