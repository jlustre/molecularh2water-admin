<?php

namespace App\Services\Portal;

use App\Mail\DemoScheduledMail;
use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Mail;

class DemoInviteMailService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function send(
        Demonstration $demonstration,
        Lead|Prospect|Customer|Recruit $lead,
        User $host,
        string $recipientEmail,
    ): void {
        $demonstration = $demonstration->fresh();
        $lead = $lead->fresh();

        $onlineLink = $demonstration->type?->isOnline()
            ? $this->settings->get(config('portal.online_demo_link_setting', 'portal.online_demo_link'))
            : null;

        Mail::to($recipientEmail)->send(new DemoScheduledMail(
            demonstration: $demonstration,
            lead: $lead,
            host: $host,
            onlineDemoLink: filled($onlineLink) ? $onlineLink : null,
        ));
    }
}
