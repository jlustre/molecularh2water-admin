<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\ActivityType;
use App\Models\Crm\LeadSource;
use App\Models\Crm\LostReason;
use App\Models\Crm\Tag;
use App\Models\Crm\Team;
use App\Models\User;
use App\Services\Crm\CrmLookupService;
use App\Services\Crm\TeamService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CrmSettingsManager extends Component
{
    use UsesCrmLayout;

    public string $activeTab = 'sources';

    public ?int $editingSourceId = null;

    public string $sourceName = '';

    public string $sourceDescription = '';

    public bool $sourceIsActive = true;

    public ?int $editingTagId = null;

    public string $tagName = '';

    public string $tagColor = 'cyan';

    public ?int $editingActivityTypeId = null;

    public string $activityTypeName = '';

    public string $activityTypeIcon = '';

    public bool $activityTypeIsActive = true;

    public ?int $editingLostReasonId = null;

    public string $lostReasonName = '';

    public string $lostReasonDescription = '';

    public bool $lostReasonRequiresDetail = false;

    public bool $lostReasonIsActive = true;

    public ?int $editingTeamId = null;

    public string $teamName = '';

    public string $teamDescription = '';

    public ?int $teamManagerId = null;

    public bool $teamIsActive = true;

    /** @var list<int> */
    public array $teamMemberIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.settings.manage'), 403);
    }

    public function setTab(string $tab): void
    {
        if ($tab === 'teams' && ! auth()->user()?->hasPermission('crm.teams.manage')) {
            return;
        }

        $this->activeTab = $tab;
        $this->cancelEdit();
    }

    public function startEditSource(int $id): void
    {
        $source = LeadSource::query()->findOrFail($id);
        $this->editingSourceId = $source->id;
        $this->sourceName = $source->name;
        $this->sourceDescription = (string) $source->description;
        $this->sourceIsActive = $source->is_active;
        $this->activeTab = 'sources';
    }

    public function saveSource(CrmLookupService $lookup): void
    {
        $this->validate([
            'sourceName' => ['required', 'string', 'max:120'],
            'sourceDescription' => ['nullable', 'string', 'max:500'],
            'sourceIsActive' => ['boolean'],
        ]);

        $lookup->upsertLeadSource($this->editingSourceId, [
            'name' => $this->sourceName,
            'description' => $this->sourceDescription,
            'is_active' => $this->sourceIsActive,
        ]);

        $this->resetSourceForm();
        session()->flash('status', 'Lead source saved.');
    }

    public function deleteSource(int $id, CrmLookupService $lookup): void
    {
        try {
            $lookup->deleteLeadSource(LeadSource::query()->findOrFail($id));
        } catch (ValidationException $exception) {
            $this->addError('item', $exception->errors()['item'][0] ?? 'Unable to delete source.');

            return;
        }

        if ($this->editingSourceId === $id) {
            $this->resetSourceForm();
        }

        session()->flash('status', 'Lead source deleted.');
    }

    public function startEditTag(int $id): void
    {
        $tag = Tag::query()->findOrFail($id);
        $this->editingTagId = $tag->id;
        $this->tagName = $tag->name;
        $this->tagColor = $tag->color ?? 'teal';
        $this->activeTab = 'tags';
    }

    public function saveTag(CrmLookupService $lookup): void
    {
        $this->validate([
            'tagName' => ['required', 'string', 'max:80'],
            'tagColor' => ['required', Rule::in(array_keys(config('crm.stage_colors', [])))],
        ]);

        $lookup->upsertTag($this->editingTagId, [
            'name' => $this->tagName,
            'color' => $this->tagColor,
        ]);

        $this->resetTagForm();
        session()->flash('status', 'Tag saved.');
    }

    public function deleteTag(int $id, CrmLookupService $lookup): void
    {
        try {
            $lookup->deleteTag(Tag::query()->findOrFail($id));
        } catch (ValidationException $exception) {
            $this->addError('item', $exception->errors()['item'][0] ?? 'Unable to delete tag.');

            return;
        }

        if ($this->editingTagId === $id) {
            $this->resetTagForm();
        }

        session()->flash('status', 'Tag deleted.');
    }

    public function startEditActivityType(int $id): void
    {
        $type = ActivityType::query()->findOrFail($id);
        $this->editingActivityTypeId = $type->id;
        $this->activityTypeName = $type->name;
        $this->activityTypeIcon = (string) $type->icon;
        $this->activityTypeIsActive = $type->is_active;
        $this->activeTab = 'activity-types';
    }

    public function saveActivityType(CrmLookupService $lookup): void
    {
        $this->validate([
            'activityTypeName' => ['required', 'string', 'max:120'],
            'activityTypeIcon' => ['nullable', 'string', 'max:40'],
            'activityTypeIsActive' => ['boolean'],
        ]);

        $lookup->upsertActivityType($this->editingActivityTypeId, [
            'name' => $this->activityTypeName,
            'icon' => $this->activityTypeIcon ?: null,
            'is_active' => $this->activityTypeIsActive,
        ]);

        $this->resetActivityTypeForm();
        session()->flash('status', 'Activity type saved.');
    }

    public function deleteActivityType(int $id, CrmLookupService $lookup): void
    {
        try {
            $lookup->deleteActivityType(ActivityType::query()->findOrFail($id));
        } catch (ValidationException $exception) {
            $this->addError('item', $exception->errors()['item'][0] ?? 'Unable to delete activity type.');

            return;
        }

        if ($this->editingActivityTypeId === $id) {
            $this->resetActivityTypeForm();
        }

        session()->flash('status', 'Activity type deleted.');
    }

    public function startEditLostReason(int $id): void
    {
        $reason = LostReason::query()->findOrFail($id);
        $this->editingLostReasonId = $reason->id;
        $this->lostReasonName = $reason->name;
        $this->lostReasonDescription = (string) $reason->description;
        $this->lostReasonRequiresDetail = $reason->requires_detail;
        $this->lostReasonIsActive = $reason->is_active;
        $this->activeTab = 'lost-reasons';
    }

    public function saveLostReason(CrmLookupService $lookup): void
    {
        $this->validate([
            'lostReasonName' => ['required', 'string', 'max:120'],
            'lostReasonDescription' => ['nullable', 'string', 'max:500'],
            'lostReasonRequiresDetail' => ['boolean'],
            'lostReasonIsActive' => ['boolean'],
        ]);

        $lookup->upsertLostReason($this->editingLostReasonId, [
            'name' => $this->lostReasonName,
            'description' => $this->lostReasonDescription,
            'requires_detail' => $this->lostReasonRequiresDetail,
            'is_active' => $this->lostReasonIsActive,
        ]);

        $this->resetLostReasonForm();
        session()->flash('status', 'Lost reason saved.');
    }

    public function deleteLostReason(int $id, CrmLookupService $lookup): void
    {
        try {
            $lookup->deleteLostReason(LostReason::query()->findOrFail($id));
        } catch (ValidationException $exception) {
            $this->addError('item', $exception->errors()['item'][0] ?? 'Unable to delete lost reason.');

            return;
        }

        if ($this->editingLostReasonId === $id) {
            $this->resetLostReasonForm();
        }

        session()->flash('status', 'Lost reason deleted.');
    }

    public function startEditTeam(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.teams.manage'), 403);

        $team = Team::query()->with('users')->findOrFail($id);
        $this->editingTeamId = $team->id;
        $this->teamName = $team->name;
        $this->teamDescription = (string) $team->description;
        $this->teamManagerId = $team->manager_id;
        $this->teamIsActive = $team->is_active;
        $this->teamMemberIds = $team->users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->activeTab = 'teams';
    }

    public function saveTeam(TeamService $teams): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.teams.manage'), 403);

        $this->validate([
            'teamName' => ['required', 'string', 'max:120'],
            'teamDescription' => ['nullable', 'string', 'max:500'],
            'teamManagerId' => ['nullable', 'integer', 'exists:users,id'],
            'teamIsActive' => ['boolean'],
            'teamMemberIds' => ['array'],
            'teamMemberIds.*' => ['integer', 'exists:users,id'],
        ]);

        $payload = [
            'name' => $this->teamName,
            'description' => $this->teamDescription,
            'manager_id' => $this->teamManagerId,
            'is_active' => $this->teamIsActive,
        ];

        if ($this->editingTeamId) {
            $teams->update(Team::query()->findOrFail($this->editingTeamId), $payload, $this->teamMemberIds);
        } else {
            $teams->create($payload, $this->teamMemberIds);
        }

        $this->resetTeamForm();
        session()->flash('status', 'Team saved.');
    }

    public function deleteTeam(int $id, TeamService $teams): void
    {
        abort_unless(auth()->user()?->hasPermission('crm.teams.manage'), 403);

        try {
            $teams->delete(Team::query()->findOrFail($id));
        } catch (ValidationException $exception) {
            $this->addError('item', $exception->errors()['item'][0] ?? 'Unable to delete team.');

            return;
        }

        if ($this->editingTeamId === $id) {
            $this->resetTeamForm();
        }

        session()->flash('status', 'Team deleted.');
    }

    public function cancelEdit(): void
    {
        $this->resetSourceForm();
        $this->resetTagForm();
        $this->resetActivityTypeForm();
        $this->resetLostReasonForm();
        $this->resetTeamForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        $assignableUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['consultant', 'manager', 'team-admin', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.crm.crm-settings-manager', [
            'sources' => LeadSource::query()->orderBy('sort_order')->get(),
            'lostReasons' => LostReason::query()->orderBy('sort_order')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'activityTypes' => ActivityType::query()->orderBy('sort_order')->get(),
            'teams' => auth()->user()?->hasPermission('crm.teams.manage')
                ? Team::query()->with(['manager', 'users'])->orderBy('name')->get()
                : collect(),
            'assignableUsers' => $assignableUsers,
            'stageColors' => config('crm.stage_colors', []),
            'canManageTeams' => auth()->user()?->hasPermission('crm.teams.manage'),
        ])->layout($this->crmLayout());
    }

    private function resetSourceForm(): void
    {
        $this->reset(['editingSourceId', 'sourceName', 'sourceDescription', 'sourceIsActive']);
        $this->sourceIsActive = true;
    }

    private function resetTagForm(): void
    {
        $this->reset(['editingTagId', 'tagName', 'tagColor']);
        $this->tagColor = 'cyan';
    }

    private function resetActivityTypeForm(): void
    {
        $this->reset(['editingActivityTypeId', 'activityTypeName', 'activityTypeIcon', 'activityTypeIsActive']);
        $this->activityTypeIsActive = true;
    }

    private function resetLostReasonForm(): void
    {
        $this->reset([
            'editingLostReasonId',
            'lostReasonName',
            'lostReasonDescription',
            'lostReasonRequiresDetail',
            'lostReasonIsActive',
        ]);
        $this->lostReasonIsActive = true;
    }

    private function resetTeamForm(): void
    {
        $this->reset([
            'editingTeamId',
            'teamName',
            'teamDescription',
            'teamManagerId',
            'teamIsActive',
            'teamMemberIds',
        ]);
        $this->teamIsActive = true;
    }
}
