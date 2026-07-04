<?php

namespace App\Livewire\Crm\Concerns;

trait UsesCrmLayout
{
    protected function crmLayout(): string
    {
        // Both admin and portal CRM pages share the authenticated shell chrome.
        // Keep distinct layout names so title / verification-banner options stay correct.
        return request()->routeIs('portal.crm.*') ? 'layouts.portal' : 'layouts.admin';
    }

    public function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'business-line-changed' => '$refresh',
        ]);
    }
}
