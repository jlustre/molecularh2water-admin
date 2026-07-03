<?php

namespace App\Livewire\Portal\Concerns;

use App\Models\RegistrationInvite;
use App\Services\RegistrationInviteService;
use Illuminate\Validation\ValidationException;

trait ManagesRegistrationInvites
{
    public string $label = '';

    public ?string $generatedUrl = null;

    public ?string $generatedCode = null;

    public bool $showEmailModal = false;

    public ?int $emailInviteId = null;

    public string $recipientEmail = '';

    public string $emailMessage = '';

    public function generateInvite(RegistrationInviteService $invites): void
    {
        abort_unless(auth()->user()?->hasPermission('invites.manage'), 403);

        $this->validate([
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $invite = $invites->generate(auth()->user(), $this->label ?: null);

        $this->generatedUrl = $invites->inviteUrl($invite);
        $this->generatedCode = $invite->code;
        $this->label = '';
        $this->resetPage();
        session()->flash('invite_status', 'Registration invite created.');
        $this->dispatch('invite-created');
    }

    public function openEmailModal(?int $inviteId = null): void
    {
        abort_unless(auth()->user()?->hasPermission('invites.manage'), 403);

        $invite = $inviteId
            ? $this->ownedInvite($inviteId)
            : $this->ownedInviteByCode($this->generatedCode);

        if (! $invite->isAvailable()) {
            session()->flash('invite_status', 'That invite is no longer available.');

            return;
        }

        $this->emailInviteId = $invite->id;
        $this->recipientEmail = '';
        $this->emailMessage = '';
        $this->showEmailModal = true;
    }

    public function closeEmailModal(): void
    {
        $this->showEmailModal = false;
        $this->emailInviteId = null;
        $this->resetValidation();
    }

    public function sendInviteEmail(RegistrationInviteService $invites): void
    {
        abort_unless(auth()->user()?->hasPermission('invites.manage'), 403);

        $this->validate([
            'recipientEmail' => ['required', 'email', 'max:255'],
            'emailMessage' => ['nullable', 'string', 'max:1000'],
        ]);

        $invite = $this->ownedInvite($this->emailInviteId);

        try {
            $invites->sendByEmail(
                $invite,
                auth()->user(),
                $this->recipientEmail,
                $this->emailMessage ?: null,
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'recipientEmail' => $exception->getMessage(),
            ]);
        }

        $this->closeEmailModal();
        session()->flash('invite_status', 'Invite email sent to '.$this->recipientEmail.'.');
    }

    /**
     * @return array{invites: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    protected function inviteViewData(): array
    {
        return [
            'invites' => RegistrationInvite::query()
                ->where('sponsor_id', auth()->id())
                ->with('registeredUser:id,name,email')
                ->latest()
                ->paginate(10),
        ];
    }

    protected function resetInviteForm(): void
    {
        $this->label = '';
        $this->generatedUrl = null;
        $this->generatedCode = null;
        $this->closeEmailModal();
        $this->resetValidation();
    }

    private function ownedInvite(int $inviteId): RegistrationInvite
    {
        return RegistrationInvite::query()
            ->where('sponsor_id', auth()->id())
            ->whereKey($inviteId)
            ->firstOrFail();
    }

    private function ownedInviteByCode(?string $code): RegistrationInvite
    {
        if (! $code) {
            abort(404);
        }

        return RegistrationInvite::query()
            ->where('sponsor_id', auth()->id())
            ->where('code', $code)
            ->firstOrFail();
    }
}
