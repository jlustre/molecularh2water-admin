<?php

namespace App\Services\Crm;

use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TimelineService
{
    public function log(
        Lead|Prospect|Customer|Recruit $contact,
        string $eventType,
        string $title,
        ?string $description = null,
        ?array $properties = null,
        ?User $user = null,
    ): TimelineEvent {
        return TimelineEvent::query()->create([
            'contact_type' => $contact->getMorphClass(),
            'contact_id' => $contact->id,
            'user_id' => ($user ?? auth()->user())?->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
