<?php

namespace App\Services\Crm;

use App\Jobs\Crm\SendFollowupSequenceStepJob;
use App\Mail\Crm\SequenceStepMail;
use App\Models\Crm\EmailTemplate;
use App\Models\Crm\Customer;
use App\Models\Crm\FollowupSequence;
use App\Models\Crm\FollowupSequenceEnrollment;
use App\Models\Crm\FollowupSequenceStep;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\SmsTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class FollowupSequenceService
{
    public function __construct(
        private readonly CrmTemplateRenderer $templates,
        private readonly TimelineService $timeline,
    ) {}

    public function enroll(Lead|Prospect|Customer|Recruit $lead, string $sequenceSlug, string $triggerEvent, User $actor): ?FollowupSequenceEnrollment
    {
        $sequence = FollowupSequence::query()
            ->where('slug', $sequenceSlug)
            ->where('is_active', true)
            ->first();

        if (! $sequence) {
            return null;
        }

        $existing = FollowupSequenceEnrollment::query()
            ->whereContact($lead)
            ->where('followup_sequence_id', $sequence->id)
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            return null;
        }

        $enrollment = FollowupSequenceEnrollment::query()->create([
            'followup_sequence_id' => $sequence->id,
            'contact_type' => $lead->getMorphClass(),
            'contact_id' => $lead->id,
            'user_id' => $actor->id,
            'trigger_event' => $triggerEvent,
            'status' => 'active',
            'current_step_order' => 0,
        ]);

        $firstStep = $sequence->steps()->orderBy('sort_order')->first();

        if ($firstStep) {
            $this->queueStep($enrollment, $firstStep);
        } else {
            $this->completeEnrollment($enrollment);
        }

        $this->timeline->log(
            $lead,
            'sequence_enrolled',
            'Enrolled in '.$sequence->name,
            null,
            ['sequence_id' => $sequence->id, 'enrollment_id' => $enrollment->id],
            $actor,
        );

        return $enrollment;
    }

    public function sendStep(int $enrollmentId, int $stepId): void
    {
        $enrollment = FollowupSequenceEnrollment::query()
            ->with(['lead', 'sequence'])
            ->find($enrollmentId);

        $step = FollowupSequenceStep::query()->find($stepId);

        if (! $enrollment || ! $step || $enrollment->status !== 'active') {
            return;
        }

        if ($enrollment->followup_sequence_id !== $step->followup_sequence_id) {
            return;
        }

        $lead = $enrollment->lead;

        if (! $lead) {
            return;
        }

        $this->dispatchStep($step, $lead);

        $enrollment->update([
            'current_step_order' => $step->sort_order,
            'next_step_at' => null,
        ]);

        $nextStep = FollowupSequenceStep::query()
            ->where('followup_sequence_id', $enrollment->followup_sequence_id)
            ->where('sort_order', '>', $step->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextStep) {
            $this->queueStep($enrollment->fresh(), $nextStep);
        } else {
            $this->completeEnrollment($enrollment);
        }
    }

    private function dispatchStep(FollowupSequenceStep $step, Lead $lead): void
    {
        if ($step->channel === 'email') {
            $template = EmailTemplate::query()->find($step->template_id);

            if ($template && $lead->email) {
                $subject = $this->templates->render($template->subject, $lead);
                $body = $this->templates->render($template->body, $lead);

                Mail::to($lead->email)->queue(new SequenceStepMail($subject, $body));
            }
        }

        if ($step->channel === 'sms') {
            $template = SmsTemplate::query()->find($step->template_id);

            if ($template && $lead->phone) {
                $body = $this->templates->render($template->body, $lead);

                $this->timeline->log(
                    $lead,
                    'sequence_sms_queued',
                    'SMS sequence step queued',
                    $body,
                    ['template_id' => $template->id, 'channel' => 'sms'],
                );
            }
        }
    }

    private function queueStep(FollowupSequenceEnrollment $enrollment, FollowupSequenceStep $step): void
    {
        $delayMinutes = max((int) $step->delay_minutes, 0);
        $runAt = now()->addMinutes($delayMinutes);

        $enrollment->update(['next_step_at' => $runAt]);

        $job = new SendFollowupSequenceStepJob($enrollment->id, $step->id);

        if ($this->shouldRunSynchronously()) {
            dispatch_sync($job);

            return;
        }

        dispatch($job)->delay($runAt);
    }

    private function completeEnrollment(FollowupSequenceEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'next_step_at' => null,
        ]);
    }

    private function shouldRunSynchronously(): bool
    {
        return (bool) config('crm.automation.sync', false)
            || app()->runningUnitTests()
            || app()->environment('testing');
    }
}
