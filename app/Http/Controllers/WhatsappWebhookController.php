<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Models\AppointmentNotification;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request, string $tenant, string $secret): JsonResponse
    {
        $model = Tenant::find($tenant);

        if ($model === null) {
            return response()->json(['ok' => false], 404);
        }

        if (! hash_equals((string) $model->webhook_secret, $secret)) {
            return response()->json(['ok' => false], 403);
        }

        $model->run(fn () => $this->process($request->all()));

        return response()->json(['ok' => true]);
    }

    private function process(array $payload): void
    {
        if (data_get($payload, 'fromMe') === true) {
            return;
        }

        $action = $this->matchAction($payload);
        $sender = $this->normalizePhone((string) (data_get($payload, 'phone') ?? data_get($payload, 'sender') ?? ''));

        if ($action === null || $sender === null) {
            Log::info('[WhatsApp] Webhook: unrecognized payload', ['payload' => $payload]);

            return;
        }

        $notification = $this->pendingConfirmation($sender);

        if ($notification === null) {
            Log::info('[WhatsApp] Webhook: no pending confirmation', ['sender' => $sender, 'action' => $action]);

            return;
        }

        $notification->appointment?->update([
            'status' => $action === 'confirm'
                ? AppointmentStatus::Confirmed->value
                : AppointmentStatus::Cancelled->value,
        ]);
    }

    private function matchAction(array $payload): ?string
    {
        $button = null;

        foreach (['buttonId', 'buttonsResponseMessage.buttonId', 'message.buttonId', 'response.id'] as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) && $value !== '') {
                $button = mb_strtolower($value);
                break;
            }
        }

        $text = mb_strtolower(trim((string) (
            $button
            ?? data_get($payload, 'text.message')
            ?? data_get($payload, 'text')
            ?? data_get($payload, 'message')
            ?? ''
        )));

        if ($text === 'confirm' || $text === '1' || str_contains($text, 'confirm')) {
            return 'confirm';
        }

        if ($text === 'cancel' || $text === '2' || str_contains($text, 'cancel')) {
            return 'cancel';
        }

        return null;
    }

    private function pendingConfirmation(string $sender): ?AppointmentNotification
    {
        return AppointmentNotification::query()
            ->where('type', NotificationType::Confirmation->value)
            ->where('status', AppointmentNotification::STATUS_SENT)
            ->whereHas('appointment', fn ($query) => $query->where('starts_at', '>', now()))
            ->with('appointment.customer')
            ->latest()
            ->get()
            ->first(fn (AppointmentNotification $notification): bool => $this->normalizePhone((string) $notification->appointment?->customer?->phone) === $sender);
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
