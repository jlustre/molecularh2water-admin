<?php

namespace App\Policies\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Model;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view')
            || $user->hasPermission('prospects.view')
            || $user->hasPermission('clients.view')
            || $user->hasPermission('recruits.view');
    }

    public function view(User $user, Lead|Prospect|Customer|Recruit $lead): bool
    {
        return CrmScope::contactIsAccessible($lead, $user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function createForLifecycle(User $user, LeadLifecycle $lifecycle): bool
    {
        return match ($lifecycle) {
            LeadLifecycle::Lead => $user->hasPermission('leads.create'),
            LeadLifecycle::Prospect => $user->hasPermission('prospects.manage')
                || $user->hasPermission('leads.create'),
            LeadLifecycle::Client => $user->hasPermission('clients.manage')
                || $user->hasPermission('leads.create'),
            LeadLifecycle::Recruit => $user->hasPermission('recruits.manage')
                || $user->hasPermission('leads.create'),
        };
    }

    public function update(User $user, Lead|Prospect|Customer|Recruit $lead): bool
    {
        $permission = match (CrmContactResolver::lifecycleForModel($lead)) {
            LeadLifecycle::Prospect => $user->hasPermission('prospects.manage') || $user->hasPermission('leads.update'),
            LeadLifecycle::Client => $user->hasPermission('clients.manage') || $user->hasPermission('leads.update'),
            LeadLifecycle::Recruit => $user->hasPermission('recruits.manage') || $user->hasPermission('leads.update'),
            default => $user->hasPermission('leads.update'),
        };

        return $permission && CrmScope::contactIsAccessible($lead, $user);
    }

    public function delete(User $user, Lead|Prospect|Customer|Recruit $lead): bool
    {
        return $user->hasPermission('leads.delete')
            && CrmScope::contactIsAccessible($lead, $user);
    }

    public function assign(User $user, Lead|Prospect|Customer|Recruit $lead): bool
    {
        return $user->hasPermission('leads.assign')
            && CrmScope::userCanViewAll($user);
    }

    public function moveOnPipeline(User $user, Lead|Prospect|Customer|Recruit $lead): bool
    {
        return $user->hasPermission('pipeline.manage')
            && CrmScope::contactIsAccessible($lead, $user);
    }
}
