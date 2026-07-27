<?php

namespace App\Models;

use App\Enums\InstallerInstallationStatus;
use App\Models\Crm\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallerInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'installer_id',
        'crm_customer_id',
        'directory_customer_id',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'street_address',
        'city',
        'state',
        'postal_code',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'rescheduled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InstallerInstallationStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rescheduled_at' => 'datetime',
        ];
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(Installer::class);
    }

    public function crmCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'crm_customer_id');
    }

    public function directoryCustomer(): BelongsTo
    {
        return $this->belongsTo(DirectoryCustomer::class);
    }

    public function locationSummary(): string
    {
        return collect([
            $this->street_address,
            $this->city,
            $this->state,
            $this->postal_code,
        ])
            ->filter()
            ->implode(', ');
    }
}
