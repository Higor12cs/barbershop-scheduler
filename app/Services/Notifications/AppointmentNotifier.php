<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\NotificationSetting;
use App\Models\ShortLink;
use App\Services\WhatsApp\ConnectorManager;
use App\Services\WhatsApp\WhatsAppConnector;

class AppointmentNotifier
{
    public function __construct(
        private ConnectorManager $connectors,
        private TemplateRenderer $renderer,
    ) {}

    public function deliver(Appointment $appointment, NotificationType $type, bool $force = false): ?AppointmentNotification
    {
        $connector = $this->connectors->forCurrent();

        if ($connector === null) {
            return null;
        }

        if (! $force && $this->alreadySent($appointment, $type)) {
            return $this->existing($appointment, $type);
        }

        $phone = $this->formatPhone($appointment->customer?->phone);

        if ($phone === null) {
            return $this->record($appointment, $type, AppointmentNotification::STATUS_FAILED, null, 'Cliente sem telefone cadastrado.');
        }

        [$delivered, $payload] = $this->dispatch($connector, $appointment, $type, $phone);

        return $this->record(
            $appointment,
            $type,
            $delivered ? AppointmentNotification::STATUS_SENT : AppointmentNotification::STATUS_FAILED,
            $payload,
            $delivered ? null : 'Falha no envio pelo WhatsApp.',
        );
    }

    public function alreadySent(Appointment $appointment, NotificationType $type): bool
    {
        return AppointmentNotification::query()
            ->where('appointment_id', $appointment->id)
            ->where('type', $type->value)
            ->where('status', AppointmentNotification::STATUS_SENT)
            ->exists();
    }

    private function dispatch(WhatsAppConnector $connector, Appointment $appointment, NotificationType $type, string $phone): array
    {
        $settings = NotificationSetting::current();

        return match ($type) {
            NotificationType::Booking => $this->dispatchBooking($connector, $appointment, $settings, $phone),
            NotificationType::Reminder => $this->dispatchText($connector, $appointment, $settings->reminder_template, $phone),
            NotificationType::Confirmation => $this->dispatchConfirmation($connector, $appointment, $settings, $phone),
        };
    }

    private function dispatchBooking(WhatsAppConnector $connector, Appointment $appointment, NotificationSetting $settings, string $phone): array
    {
        $calendarUrl = $this->calendarLink($appointment);
        $message = $this->renderer->render($settings->booking_template, $appointment, ['{link_calendario}' => $calendarUrl]);

        $delivered = $connector->sendText($phone, $message) !== false;

        return [$delivered, ['phone' => $phone, 'message' => $message, 'calendar_url' => $calendarUrl]];
    }

    private function dispatchText(WhatsAppConnector $connector, Appointment $appointment, ?string $template, string $phone): array
    {
        $message = $this->renderer->render($template, $appointment);

        $delivered = $connector->sendText($phone, $message) !== false;

        return [$delivered, ['phone' => $phone, 'message' => $message]];
    }

    private function dispatchConfirmation(WhatsAppConnector $connector, Appointment $appointment, NotificationSetting $settings, string $phone): array
    {
        $message = $this->renderer->render($settings->confirmation_template, $appointment);
        $buttons = [
            ['id' => 'confirm', 'label' => 'Confirmar'],
            ['id' => 'cancel', 'label' => 'Cancelar'],
        ];

        $delivered = $connector->sendButtons($phone, $message, $buttons) !== false;

        return [$delivered, ['phone' => $phone, 'message' => $message, 'buttons' => $buttons]];
    }

    private function calendarLink(Appointment $appointment): string
    {
        return ShortLink::forCalendar([
            'title' => (string) $appointment->product?->name,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'description' => trim(sprintf(
                '%s com %s',
                (string) $appointment->product?->name,
                (string) $appointment->employee?->name,
            )),
            'location' => (string) (tenant('name') ?? ''),
        ]);
    }

    private function record(Appointment $appointment, NotificationType $type, string $status, ?array $payload, ?string $error): AppointmentNotification
    {
        return AppointmentNotification::updateOrCreate(
            ['appointment_id' => $appointment->id, 'type' => $type->value],
            [
                'status' => $status,
                'payload' => $payload,
                'error' => $error,
                'sent_at' => $status === AppointmentNotification::STATUS_SENT ? now() : null,
            ],
        );
    }

    private function existing(Appointment $appointment, NotificationType $type): ?AppointmentNotification
    {
        return AppointmentNotification::query()
            ->where('appointment_id', $appointment->id)
            ->where('type', $type->value)
            ->first();
    }

    private function formatPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
