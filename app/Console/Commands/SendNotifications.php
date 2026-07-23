<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\NotificationSetting;
use App\Models\Tenant;
use App\Services\Notifications\AppointmentNotifier;
use App\Support\NotificationWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        $settings = NotificationSetting::current();
        $window = $settings->sendWindow();
        $now = now();

        if (! $window->isOpen($now)) {
            return;
        }

        $notifier = app(AppointmentNotifier::class);
        $statuses = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed];

        if ($settings->reminder_enabled) {
            $this->due(NotificationType::Reminder, $settings->reminder_minutes_before, $statuses, $window, $now)
                ->each(fn (Appointment $appointment) => $notifier->deliver($appointment, NotificationType::Reminder));
        }

        if ($settings->confirmation_enabled) {
            $this->due(NotificationType::Confirmation, $settings->confirmation_minutes_before, $statuses, $window, $now)
                ->each(fn (Appointment $appointment) => $notifier->deliver($appointment, NotificationType::Confirmation));
        }
    }

    /**
     * Appointments whose message is already due.
     *
     * The window can pull a send backwards by up to a full day, so the query
     * casts a wider net than the configured lead time and the exact due moment
     * is settled in memory.
     *
     * @param  array<int, AppointmentStatus>  $statuses
     * @return Collection<int, Appointment>
     */
    private function due(NotificationType $type, int $minutes, array $statuses, NotificationWindow $window, Carbon $now): Collection
    {
        return Appointment::query()
            ->whereIn('status', array_map(fn (AppointmentStatus $status): string => $status->value, $statuses))
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $now->copy()->addMinutes($window->lookaheadMinutes($minutes)))
            ->whereDoesntHave('notifications', fn ($query) => $query
                ->where('type', $type->value)
                ->where('status', AppointmentNotification::STATUS_SENT))
            ->with(['customer', 'employee', 'product'])
            ->get()
            ->filter(fn (Appointment $appointment): bool => $window
                ->dueAt($appointment->starts_at, $minutes)
                ->lessThanOrEqualTo($now))
            ->values();
    }
}
