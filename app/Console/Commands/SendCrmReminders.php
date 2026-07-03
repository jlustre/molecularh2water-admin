<?php

namespace App\Console\Commands;

use App\Enums\Crm\AppointmentStatus;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Appointment;
use App\Models\Crm\Task;
use App\Notifications\Crm\AppointmentReminderNotification;
use App\Notifications\Crm\TaskReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendCrmReminders extends Command
{
    protected $signature = 'crm:send-reminders';

    protected $description = 'Send CRM task and appointment reminders';

    public function handle(): int
    {
        $tasksSent = $this->sendTaskReminders();
        $appointmentsSent = $this->sendAppointmentReminders();

        $this->info("Sent {$tasksSent} task reminder(s) and {$appointmentsSent} appointment reminder(s).");

        return self::SUCCESS;
    }

    private function sendTaskReminders(): int
    {
        $sent = 0;

        Task::query()
            ->with('user')
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])
            ->each(function (Task $task) use (&$sent) {
                $user = $task->user;

                if (! $user) {
                    $task->update(['reminder_at' => null]);

                    return;
                }

                $cacheKey = 'crm.task.reminder.'.$task->id;

                if (Cache::has($cacheKey)) {
                    return;
                }

                $user->notify(new TaskReminderNotification($task));
                Cache::put($cacheKey, true, now()->addDay());
                $task->update(['reminder_at' => null]);
                $sent++;
            });

        return $sent;
    }

    private function sendAppointmentReminders(): int
    {
        $sent = 0;
        $windowEnd = now()->addHour();

        Appointment::query()
            ->with('user')
            ->where('status', AppointmentStatus::Scheduled->value)
            ->whereBetween('starts_at', [now(), $windowEnd])
            ->each(function (Appointment $appointment) use (&$sent) {
                $user = $appointment->user;

                if (! $user) {
                    return;
                }

                $cacheKey = 'crm.appointment.reminder.'.$appointment->id;

                if (Cache::has($cacheKey)) {
                    return;
                }

                $user->notify(new AppointmentReminderNotification($appointment));
                Cache::put($cacheKey, true, $appointment->starts_at ?? now()->addHour());
                $sent++;
            });

        return $sent;
    }
}
