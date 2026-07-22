<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\NotificationSetting;
use App\Models\Tenant;
use App\Services\Notifications\AppointmentNotifier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SendNotifications extends Command
{
    protected $signature = 'notifications:send';

    protected $description = 'Send appointment reminders and confirmations via WhatsApp';

    public function handle(): int
    {
        Tenant::query()
            ->where('active', true)
            ->whereNotNull('whatsapp_provider')
            ->cursor()
            ->each(function (Tenant $tenant): void {
                $tenant->run(fn () => $this->processTenant());
            });

        return self::SUCCESS;
    }

    private function processTenant(): void
    {
        $notifier = app(AppointmentNotifier::class);
        $settings = NotificationSetting::current();

        $statuses = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed];

        if ($settings->reminder_enabled) {
            $this->pending(NotificationType::Reminder, $settings->reminder_minutes_before, $statuses)
                ->each(fn (Appointment $appointment) => $notifier->deliver($appointment, NotificationType::Reminder));
        }

        if ($settings->confirmation_enabled) {
            $this->pending(NotificationType::Confirmation, $settings->confirmation_minutes_before, $statuses)
                ->each(fn (Appointment $appointment) => $notifier->deliver($appointment, NotificationType::Confirmation));
        }
    }

    private function pending(NotificationType $type, int $minutes, array $statuses): Collection
    {
        return Appointment::query()
            ->whereIn('status', array_map(fn (AppointmentStatus $status): string => $status->value, $statuses))
            ->whereBetween('starts_at', [now(), now()->addMinutes($minutes)])
            ->whereDoesntHave('notifications', fn ($query) => $query
                ->where('type', $type->value)
                ->where('status', AppointmentNotification::STATUS_SENT))
            ->with(['customer', 'employee', 'product'])
            ->get();
    }
}
