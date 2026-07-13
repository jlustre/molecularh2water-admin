<?php

namespace App\Livewire\Portal;

use App\Livewire\Portal\Concerns\ManagesRegistrationInvites;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationInvites extends Component
{
    use ManagesRegistrationInvites;
    use WithPagination;

    public function mount(): void
    {
        $this->sponsorUserId = auth()->id();
    }

    public function render()
    {
        return view('livewire.portal.registration-invites', $this->inviteViewData())
            ->layout('layouts.portal', ['header' => 'Member Invites']);
    }
}
