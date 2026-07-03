<?php

namespace App\Notifications\Crm;

use App\Models\Crm\Appointment;
use App\Support\Crm\CrmRoutes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
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
        return (new MailMessage)
            ->subject('Upcoming appointment: '.$this->appointment->title)
            ->line('You have an appointment starting soon.')
            ->line($this->appointment->title)
            ->line('Starts at: '.$this->appointment->starts_at?->format('M j, Y g:i A'))
            ->action('View calendar', CrmRoutes::urlForUser($notifiable, 'calendar.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => $this->appointment->title,
            'starts_at' => $this->appointment->starts_at?->toIso8601String(),
            'lead_id' => $this->appointment->lead_id,
            'message' => 'Appointment reminder: '.$this->appointment->title,
        ];
    }
}
