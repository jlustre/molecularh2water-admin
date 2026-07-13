<?php

namespace App\Livewire\Portal;

use App\Livewire\Portal\Concerns\ManagesRegistrationInvites;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationInvitesModal extends Component
{
    use ManagesRegistrationInvites;
    use WithPagination;

    public bool $show = false;

    #[On('open-member-invites')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('invites.manage'), 403);

        $this->resetInviteForm();
        $this->sponsorUserId = auth()->id();
        $this->resetPage();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetInviteForm();
    }

    public function render()
    {
        return view('livewire.portal.registration-invites-modal', $this->inviteViewData());
    }
}
