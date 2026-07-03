<?php

namespace App\Models\Crm;

use App\Contracts\Crm\CrmContact;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\Concerns\IsCrmContact;
use App\Support\Crm\CrmScope;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'lifecycle_id',
    'business_line',
    'status',
    'temperature',
    'score',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'city',
    'state',
    'country',
    'company',
    'occupation',
    'spouse_name',
    'spouse_occupation',
    'best_time_to_contact',
    'lead_source_id',
    'funnel_id',
    'funnel_stage_id',
    'lost_reason_id',
    'assigned_user_id',
    'referred_by_type',
    'referred_by_id',
    'team_id',
    'interested_in',
    'message',
    'lost_reason',
    'last_contacted_at',
    'next_follow_up_at',
    'converted_at',
    'consent_given',
    'metadata',
])]
class Customer extends Model implements CrmContact
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, IsCrmContact, SoftDeletes;

    protected $table = 'customers';

    protected function casts(): array
    {
        return $this->crmContactCasts();
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    public function scopeHot($query)
    {
        return $query->where('temperature', LeadTemperature::Hot->value);
    }

    public function scopeFollowUpDueToday($query)
    {
        return $query->whereDate('next_follow_up_at', '<=', now()->toDateString())
            ->whereNotNull('next_follow_up_at');
    }
}
