<?php

namespace App\Jobs\Crm;

use App\Services\Crm\CrmAutomationService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunCrmAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $event,
        public array $context,
        public int $actorId,
    ) {
        $this->onQueue(config('crm.automation.queue', 'default'));
    }

    public function handle(CrmAutomationService $automation): void
    {
        $actor = User::query()->find($this->actorId);

        if (! $actor) {
            return;
        }

        $automation->handle($this->event, $this->context, $actor);
    }
}
