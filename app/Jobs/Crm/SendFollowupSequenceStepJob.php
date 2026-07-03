<?php

namespace App\Jobs\Crm;

use App\Services\Crm\FollowupSequenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFollowupSequenceStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $enrollmentId,
        public int $stepId,
    ) {
        $this->onQueue(config('crm.automation.queue', 'default'));
    }

    public function handle(FollowupSequenceService $sequences): void
    {
        $sequences->sendStep($this->enrollmentId, $this->stepId);
    }
}
