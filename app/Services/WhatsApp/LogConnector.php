<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class LogConnector implements WhatsAppConnector
{
    public function __construct(private array $config = []) {}

    public function connectionStatus(): array
    {
        return [
            'connected' => true,
            'phone' => (string) ($this->config['phone'] ?? '5511999999999'),
            'status' => 'connected',
        ];
    }

    public function getQrCode(): ?string
    {
        return null;
    }

    public function sendText(string $phone, string $message): array|bool
    {
        return $this->record('text', $phone, ['message' => $message]);
    }

    public function sendImage(string $phone, string $url, ?string $caption = null): array|bool
    {
        return $this->record('image', $phone, ['url' => $url, 'caption' => $caption]);
    }

    public function sendFile(string $phone, string $url, ?string $filename = null): array|bool
    {
        return $this->record('file', $phone, ['url' => $url, 'filename' => $filename]);
    }

    public function sendButtons(string $phone, string $message, array $buttons): array|bool
    {
        return $this->record('buttons', $phone, ['message' => $message, 'buttons' => $buttons]);
    }

    public function restart(): bool
    {
        Log::info('[WhatsApp][Log] restart');

        return true;
    }

    public function lastError(): ?string
    {
        return null;
    }

    private function record(string $kind, string $phone, array $payload): array
    {
        Log::info('[WhatsApp][Log] send '.$kind, [
            'phone' => $phone,
            ...$payload,
        ]);

        return ['delivered' => true, 'kind' => $kind, 'phone' => $phone];
    }
}
