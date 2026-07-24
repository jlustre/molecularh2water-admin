<?php

namespace App\Notifications\Crm;

use App\Contracts\Crm\CrmContact;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Support\Crm\CrmRoutes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmLeadCapturedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead|Prospect|CrmContact $lead,
        public ?string $sourceLabel = null,
    ) {
        $this->onQueue(config('crm.queue.notifications', 'default'));
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->lead->fullName();
        $routeName = $this->lead instanceof Prospect ? 'prospects.show' : 'leads.show';

        return (new MailMessage)
            ->subject('New CRM capture: '.$name)
            ->line('A new prospect was captured'.($this->sourceLabel ? " from {$this->sourceLabel}" : '').'.')
            ->line($name.' · '.($this->lead->email ?? $this->lead->phone ?? 'No contact'))
            ->action('View in CRM', CrmRoutes::urlForUser($notifiable, $routeName, ['lead' => $this->lead]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'name' => $this->lead->fullName(),
            'email' => $this->lead->email,
            'source' => $this->sourceLabel,
            'message' => 'New prospect captured: '.$this->lead->fullName(),
        ];
    }
}
