<?php

namespace App\Contracts\Crm;

use App\Enums\Crm\LeadLifecycle;

interface CrmContact
{
    public function fullName(): string;

    public function lifecycleSlug(): LeadLifecycle;
}
