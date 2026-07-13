<?php

namespace App\Livewire\Portal\Concerns;

use App\Models\RegistrationInvite;
use App\Services\InviteSponsorScopeService;
use App\Services\RegistrationInviteService;
use Illuminate\Validation\ValidationException;

trait ManagesRegistrationInvites
{
    public ?int $sponsorUserId = null;

    public ?string $generatedUrl = null;

    public ?string $generatedCode = null;

    public bool $showEmailModal = false;

    public ?int $emailInviteId = null;

    public string $recipientEmail = '';

    public string $emailMessage = '';

    public function generateInvite(RegistrationInviteService $invites, InviteSponsorScopeService $sponsors): void
    {
        $actor = auth()->user();
        abort_unless($actor?->hasPermission('invites.manage'), 403);

        $this->validate([
            'sponsorUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $sponsor = $sponsors->assertInScope($actor, (int) $this->sponsorUserId);
        $invite = $invites->generate($sponsor, $actor);

        $this->generatedUrl = $invites->inviteUrl($invite);
        $this->generatedCode = $invite->code;
        $this->sponsorUserId = $actor->id;
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
     * @return array{
     *     invites: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     sponsorOptions: \Illuminate\Support\Collection<int, \App\Models\User>
     * }
     */
    protected function inviteViewData(): array
    {
        $user = auth()->user();

        return [
            'invites' => RegistrationInvite::query()
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                        ->orWhere('sponsor_id', $user->id);
                })
                ->with(['sponsor:id,name', 'registeredUser:id,name,email'])
                ->latest()
                ->paginate(10),
            'sponsorOptions' => app(InviteSponsorScopeService::class)->optionsFor($user),
        ];
    }

    protected function resetInviteForm(): void
    {
        $this->sponsorUserId = auth()->id();
        $this->generatedUrl = null;
        $this->generatedCode = null;
        $this->closeEmailModal();
        $this->resetValidation();
    }

    private function ownedInvite(int $inviteId): RegistrationInvite
    {
        return RegistrationInvite::query()
            ->where(function ($query) {
                $query->where('created_by', auth()->id())
                    ->orWhere('sponsor_id', auth()->id());
            })
            ->whereKey($inviteId)
            ->firstOrFail();
    }

    private function ownedInviteByCode(?string $code): RegistrationInvite
    {
        if (! $code) {
            abort(404);
        }

        return RegistrationInvite::query()
            ->where(function ($query) {
                $query->where('created_by', auth()->id())
                    ->orWhere('sponsor_id', auth()->id());
            })
            ->where('code', $code)
            ->firstOrFail();
    }
}
