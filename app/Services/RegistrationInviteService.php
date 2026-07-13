<?php

namespace App\Services;

use App\Mail\RegistrationInviteMail;
use App\Models\RegistrationInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationInviteService
{
    public function generate(User $sponsor, User $createdBy): RegistrationInvite
    {
        $ttlDays = config('registration.invite_ttl_days', 30);

        return RegistrationInvite::query()->create([
            'sponsor_id' => $sponsor->id,
            'created_by' => $createdBy->id,
            'code' => $this->uniqueCode(),
            'label' => null,
            'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
        ]);
    }

    public function findValidInvite(string $code): ?RegistrationInvite
    {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return null;
        }

        return RegistrationInvite::query()
            ->available()
            ->where('code', $code)
            ->with('sponsor:id,name')
            ->first();
    }

    public function inviteUrl(RegistrationInvite $invite): string
    {
        return route('register.invite', ['code' => $invite->code]);
    }

    public function sendByEmail(
        RegistrationInvite $invite,
        User $actor,
        string $recipientEmail,
        ?string $personalMessage = null,
    ): void {
        if (! $invite->isAvailable()) {
            throw new \InvalidArgumentException('This invite is no longer available.');
        }

        if ((int) $invite->sponsor_id !== (int) $actor->id
            && (int) $invite->created_by !== (int) $actor->id) {
            throw new \InvalidArgumentException('You can only send invites that you created.');
        }

        Mail::to($recipientEmail)->send(
            new RegistrationInviteMail(
                $invite,
                $invite->sponsor ?? $actor,
                $this->inviteUrl($invite),
                $personalMessage ? trim($personalMessage) : null,
            ),
        );
    }

    /**
     * Mark an invite as used and return the sponsor id for the new user.
     */
    public function consume(RegistrationInvite $invite, User $registeredUser): void
    {
        DB::transaction(function () use ($invite, $registeredUser) {
            $locked = RegistrationInvite::query()
                ->whereKey($invite->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || ! $locked->isAvailable()) {
                throw new \RuntimeException('This invite code is no longer valid.');
            }

            $locked->update([
                'registered_user_id' => $registeredUser->id,
                'consumed_at' => now(),
            ]);
        });
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(implode('-', [
                Str::upper(Str::random(4)),
                Str::upper(Str::random(4)),
            ]));
        } while (RegistrationInvite::query()->where('code', $code)->exists());

        return $code;
    }
}
