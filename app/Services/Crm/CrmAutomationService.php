<?php

namespace App\Services\Crm;

use App\Enums\Crm\DemonstrationType;
use App\Jobs\Crm\RunCrmAutomationJob;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Delivery;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Models\Crm\Order;
use App\Models\User;
use App\Notifications\Crm\DemoScheduledNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CrmAutomationService
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly CalendarEventService $calendar,
        private readonly FunnelService $funnels,
        private readonly FollowupSequenceService $sequences,
        private readonly CrmTemplateRenderer $templates,
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function dispatch(string $event, array $context, User $actor): void
    {
        if (! config('crm.automation.enabled', true)) {
            return;
        }

        $context = $this->normalizeContext($context);

        if ($this->shouldRunSynchronously()) {
            $this->handle($event, $context, $actor);

            return;
        }

        RunCrmAutomationJob::dispatch($event, $context, $actor->id);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(string $event, array $context, User $actor): void
    {
        if (! config('crm.automation.enabled', true)) {
            return;
        }

        $context = $this->normalizeContext($context);
        $lead = $this->resolveLead($context);

        if (! $lead) {
            return;
        }

        $rules = $this->rulesForEvent($event, $context);

        foreach ($rules as $rule) {
            $this->runAction($rule, $lead, $context, $actor);
        }

        $sequenceSlug = Arr::get(config('crm.automation.sequences', []), $event);

        if ($sequenceSlug) {
            $this->sequences->enroll($lead, $sequenceSlug, $event, $actor);
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    private function runAction(array $rule, Lead $lead, array $context, User $actor): void
    {
        $action = Arr::get($rule, 'action');

        match ($action) {
            'calendar_event' => $this->createCalendarEvent($rule, $lead, $context, $actor),
            'task' => $this->createTask($rule, $lead, $context, $actor),
            'notify_assignee' => $this->notifyAssignee($rule, $lead, $context, $actor),
            'move_stage' => $this->moveStage($rule, $lead, $actor),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    private function createCalendarEvent(array $rule, Lead $lead, array $context, User $actor): void
    {
        $typeSlug = Arr::get($rule, 'event_type', 'home-demo');
        $type = CalendarEventType::query()->where('slug', $typeSlug)->first()
            ?? CalendarEventType::query()->orderBy('sort_order')->first();

        if (! $type) {
            return;
        }

        $demo = $this->resolveDemonstration($context);
        $startAt = $demo?->scheduled_at ?? now()->addDay();
        $duration = $demo?->duration_minutes ?? 60;
        $title = $this->templates->render(
            Arr::get($rule, 'title', 'Calendar event'),
            $lead,
            $this->actionVariables($lead, $context),
        );

        $event = $this->calendar->create([
            'calendar_event_type_id' => $type->id,
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_user_id ?: $actor->id,
            'title' => $title,
            'description' => $demo?->notes,
            'start_at' => $startAt,
            'end_at' => $startAt instanceof Carbon ? $startAt->copy()->addMinutes($duration) : Carbon::parse($startAt)->addMinutes($duration),
            'location' => $demo?->venue,
            'metadata' => array_filter([
                'automation' => true,
                'demonstration_id' => $demo?->id,
                'demonstration_type' => $demo?->type?->value,
            ]),
        ], $actor);

        if ($demo && ! $demo->calendar_event_id) {
            $demo->update(['calendar_event_id' => $event->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    private function createTask(array $rule, Lead $lead, array $context, User $actor): void
    {
        $dueDays = (int) Arr::get($rule, 'due_days', 1);
        $title = $this->templates->render(
            Arr::get($rule, 'title', 'Follow-up task'),
            $lead,
            $this->actionVariables($lead, $context),
        );

        $this->tasks->create([
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_user_id ?: $actor->id,
            'title' => $title,
            'description' => Arr::get($rule, 'description'),
            'priority' => Arr::get($rule, 'priority', 'normal'),
            'due_at' => now()->addDays(max($dueDays, 0)),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    private function notifyAssignee(array $rule, Lead $lead, array $context, User $actor): void
    {
        $recipient = $lead->assignedUser ?: $actor;
        $notification = Arr::get($rule, 'notification');

        if ($notification === 'demo_scheduled') {
            $demo = $this->resolveDemonstration($context);

            if ($demo) {
                $recipient->notify(new DemoScheduledNotification($lead, $demo));
            }

            return;
        }

        $this->timeline->log(
            $lead,
            'automation_notification',
            'Automation notification queued',
            $notification,
            ['notification' => $notification],
            $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function moveStage(array $rule, Lead $lead, User $actor): void
    {
        $slug = Arr::get($rule, 'stage_slug');

        if (! $slug) {
            return;
        }

        $this->funnels->moveLeadToStageSlug($lead, $slug, $actor);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        if (isset($context['lead']) && $context['lead'] instanceof Lead) {
            $context['lead_id'] = $context['lead']->id;
            unset($context['lead']);
        }

        if (isset($context['demonstration']) && $context['demonstration'] instanceof Demonstration) {
            $context['demonstration_id'] = $context['demonstration']->id;
            unset($context['demonstration']);
        }

        if (isset($context['order']) && $context['order'] instanceof Order) {
            $context['order_id'] = $context['order']->id;
            unset($context['order']);
        }

        if (isset($context['delivery']) && $context['delivery'] instanceof Delivery) {
            $context['delivery_id'] = $context['delivery']->id;
            unset($context['delivery']);
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveLead(array $context): ?Lead
    {
        if (! $leadId = Arr::get($context, 'lead_id')) {
            return null;
        }

        return Lead::query()->find($leadId);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveDemonstration(array $context): ?Demonstration
    {
        if (! $demoId = Arr::get($context, 'demonstration_id')) {
            return null;
        }

        return Demonstration::query()->find($demoId);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function rulesForEvent(string $event, array $context): array
    {
        if ($event === 'stage.moved') {
            $slug = Arr::get($context, 'stage_slug');
            $stageRules = Arr::get(config('crm.automation.rules', []), 'stage.moved', []);

            return $slug
                ? (array) Arr::get($stageRules, $slug, [])
                : [];
        }

        return (array) Arr::get(config('crm.automation.rules', []), $event, []);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function actionVariables(Lead $lead, array $context): array
    {
        $variables = [];

        if ($demo = $this->resolveDemonstration($context)) {
            $variables['demo_type'] = $demo->type instanceof DemonstrationType ? $demo->type->label() : (string) $demo->type;
            $variables['demo_date'] = $demo->scheduled_at?->format('M j, Y g:i A') ?? '';
        }

        if ($orderId = Arr::get($context, 'order_id')) {
            $order = Order::query()->find($orderId);
            $variables['order_number'] = $order?->order_number ?? '';
        }

        return $variables;
    }

    private function shouldRunSynchronously(): bool
    {
        return (bool) config('crm.automation.sync', false)
            || app()->runningUnitTests()
            || app()->environment('testing');
    }
}
