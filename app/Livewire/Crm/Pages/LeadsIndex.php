<?php

namespace App\Livewire\Crm\Pages;

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\LeadTable;

class LeadsIndex extends LeadTable
{
    public function mount(LeadLifecycle|string $lifecycle = LeadLifecycle::Lead): void
    {
        parent::mount(LeadLifecycle::Lead);
    }
}
