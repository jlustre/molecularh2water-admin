<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar_path', 'sponsor_id', 'business_lines'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'business_lines' => 'array',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Crm\Team::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function sponsor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_id');
    }

    public function sponsoredUsers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'sponsor_id');
    }

    public function registrationInvites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RegistrationInvite::class, 'sponsor_id');
    }

    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Crm\CalendarEvent::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    public function permissions(): array
    {
        if ($this->hasRole('super-admin')) {
            return \App\Support\Crm\CrmPermissions::all();
        }

        return $this->roles()
            ->where('status', 'active')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions ?? [])
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permissions = $this->permissions();

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // Legacy permission aliases
        return match ($permission) {
            'leads.view', 'leads.create', 'leads.update', 'leads.delete', 'leads.import', 'leads.export', 'leads.assign' => in_array('leads.manage', $permissions, true),
            'appointments.view' => in_array('appointments.manage', $permissions, true),
            'roles.view' => in_array('roles.manage', $permissions, true),
            'warranty.view' => in_array('warranty.manage', $permissions, true),
            'installation-questionnaires.view' => in_array('installation-questionnaires.manage', $permissions, true),
            'website-forms.view' => in_array('website-forms.manage', $permissions, true)
                || in_array('messages.manage', $permissions, true),
            'website-forms.manage' => in_array('messages.manage', $permissions, true),
            'email-mappings.view' => in_array('email-mappings.manage', $permissions, true),
            default => false,
        };
    }

    public function canAccessAdmin(): bool
    {
        return $this->hasPermission('admin.dashboard.view')
            || $this->hasRole(['super-admin', 'admin', 'team-admin', 'editor']);
    }

    public function canAccessPortal(): bool
    {
        return $this->hasPermission('portal.dashboard.view')
            || $this->hasPermission('crm.dashboard.view')
            || $this->canAccessAdmin();
    }

    public function isConsultant(): bool
    {
        return $this->hasRole('consultant');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function requiresSponsor(): bool
    {
        return ! $this->isSuperAdmin();
    }

    public function canViewAllSponsorTrees(): bool
    {
        return $this->hasPermission('users.view') || $this->isSuperAdmin();
    }

    public function canViewAllCrmRecords(): bool
    {
        return $this->hasPermission('crm.records.view-all');
    }

    /**
     * @return list<string>
     */
    public function resolvedBusinessLineValues(): array
    {
        $assignable = array_map(
            fn (\App\Enums\BusinessLine $line) => $line->value,
            \App\Enums\BusinessLine::assignableCases(),
        );

        if ($this->isSuperAdmin()) {
            return $assignable;
        }

        $lines = collect($this->business_lines ?? [])
            ->filter()
            ->map(fn ($line) => (string) $line)
            ->filter(fn (string $line) => in_array($line, $assignable, true))
            ->unique()
            ->values()
            ->all();

        return $lines !== [] ? $lines : [\App\Enums\BusinessLine::H2s->value];
    }

    public function participatesInBusinessLine(\App\Enums\BusinessLine|string $line): bool
    {
        $value = $line instanceof \App\Enums\BusinessLine ? $line->value : $line;

        return in_array($value, $this->resolvedBusinessLineValues(), true);
    }

    public function participatesInMultipleBusinessLines(): bool
    {
        return count($this->resolvedBusinessLineValues()) > 1;
    }

    /**
     * Public avatar URL with a cache-busting query so browsers pick up replacements.
     *
     * Served via AvatarController (/avatars/{filename}) so images work even when
     * public/storage cannot be symlinked (common on shared hosting).
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        $url = route('avatars.show', ['filename' => basename($this->avatar_path)]);
        $version = $this->updated_at?->getTimestamp() ?? time();

        return $url.'?v='.$version;
    }
}
