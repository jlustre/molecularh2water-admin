<?php

namespace App\Models\Crm\Concerns;

use App\Models\User;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait BelongsToCrmContact
{
    public function contact(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @deprecated Use contact() — alias for eager loading polymorphic CRM contacts.
     */
    public function lead(): MorphTo
    {
        return $this->morphTo('contact', 'contact_type', 'contact_id');
    }

    public function getLeadIdAttribute(): ?int
    {
        return $this->contact_type === 'lead' ? $this->contact_id : null;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereLeadId($query, int $leadId)
    {
        return $query->where('contact_type', 'lead')->where('contact_id', $leadId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereContact($query, Model $contact)
    {
        return $query
            ->where('contact_type', $contact->getMorphClass())
            ->where('contact_id', $contact->id);
    }

    /**
     * Limit rows to contacts the user may access (all contact morph types).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAccessibleContacts($query, ?User $user = null)
    {
        return $query->whereHasMorph(
            'contact',
            ['lead', 'prospect', 'customer', 'recruit'],
            fn (Builder $contactQuery) => CrmScope::contacts($contactQuery, $user),
        );
    }
}
