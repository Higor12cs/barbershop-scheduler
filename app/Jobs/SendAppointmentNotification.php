<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Services\Notifications\AppointmentNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAppointmentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public int $appointmentId,
        public string $type,
    ) {}

    public function handle(AppointmentNotifier $notifier): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant === null) {
            return;
        }

        $tenant->run(function () use ($notifier): void {
            try {
                $appointment = Appointment::query()
                    ->with(['customer', 'employee', 'product'])
                    ->find($this->appointmentId);

                if ($appointment === null) {
                    return;
                }

                $notifier->deliver($appointment, NotificationType::from($this->type));
            } catch (Throwable $exception) {
                Log::warning('[WhatsApp] Notification delivery failed', [
                    'tenant' => $this->tenantId,
                    'appointment_id' => $this->appointmentId,
                    'type' => $this->type,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
