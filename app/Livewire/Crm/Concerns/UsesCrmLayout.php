<?php



namespace App\Livewire\Crm\Concerns;



trait UsesCrmLayout

{

    protected function crmLayout(): string

    {

        return request()->routeIs('portal.crm.*') ? 'layouts.portal' : 'layouts.admin';

    }



    public function getListeners(): array

    {

        return array_merge(parent::getListeners(), [

            'business-line-changed' => '$refresh',

        ]);

    }

}

