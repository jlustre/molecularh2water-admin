<?php

namespace App\Livewire\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Note;
use App\Models\Crm\TimelineEvent;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;
use Livewire\Component;

class LeadTimeline extends Component
{
    public Lead|Prospect|Customer|Recruit $lead;

    /** @var Collection<int, object> */
    public Collection $entries;

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->entries = $this->buildTimeline($lead);
    }

    public function refreshTimeline(): void
    {
        $this->entries = $this->buildTimeline($this->lead->fresh());
    }

    protected function getListeners(): array
    {
        return [
            'note-added' => 'refreshTimeline',
            'engagement-updated' => 'refreshTimeline',
        ];
    }

    public function render()
    {
        return view('livewire.crm.lead-timeline');
    }

    /**
     * @return Collection<int, object>
     */
    private function buildTimeline(Lead|Prospect|Customer|Recruit $lead): Collection
    {
        $events = CrmScope::timelineEvents(TimelineEvent::query())
            ->where('contact_type', $lead->getMorphClass())
            ->where('contact_id', $lead->id)
            ->whereNotIn('event_type', ['note_added', 'activity_logged'])
            ->with('user')
            ->get()
            ->map(fn (TimelineEvent $event) => (object) [
                'type' => 'event',
                'title' => $event->title,
                'body' => $event->description,
                'user' => $event->user?->name,
                'created_at' => $event->created_at,
            ]);

        $notes = Note::query()
            ->where('noteable_type', $lead->getMorphClass())
            ->where('noteable_id', $lead->id)
            ->with('user')
            ->get()
            ->map(fn (Note $note) => (object) [
                'type' => 'note',
                'title' => 'Note',
                'body' => $note->body,
                'user' => $note->user?->name,
                'created_at' => $note->created_at,
            ]);

        $activities = Activity::query()
            ->where('contact_type', $lead->getMorphClass())
            ->where('contact_id', $lead->id)
            ->with(['user', 'type'])
            ->get()
            ->map(fn (Activity $activity) => (object) [
                'type' => 'activity',
                'title' => $activity->type?->name ?? $activity->title,
                'body' => trim(collect([$activity->title, $activity->description])->filter()->implode("\n\n")),
                'user' => $activity->user?->name,
                'created_at' => $activity->completed_at ?? $activity->created_at,
            ]);

        return $events
            ->concat($notes)
            ->concat($activities)
            ->sortByDesc('created_at')
            ->values();
    }
}
