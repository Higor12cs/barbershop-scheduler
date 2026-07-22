<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DiChatConnector implements WhatsAppConnector
{
    private string $baseUrl;

    private string $token;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api11.dichat.com.br'), '/');
        $this->token = (string) ($config['token'] ?? '');
    }

    public function connectionStatus(): array
    {
        $connection = $this->currentConnection();

        $connected = $connection !== null
            && mb_strtoupper((string) ($connection['status'] ?? '')) === 'CONNECTED';

        return [
            'connected' => $connected,
            'phone' => $connection['number'] ?? $connection['name'] ?? null,
            'status' => $connected ? 'connected' : 'disconnected',
        ];
    }

    public function getQrCode(): ?string
    {
        $response = $this->client()->post('/api/whatsappqrcodepro');

        if (! $response->successful()) {
            return null;
        }

        $value = (string) ($response->json('qrcode.data.data.QRCode') ?? '');

        return $value !== '' ? $value : null;
    }

    public function sendText(string $phone, string $message): array|bool
    {
        $response = $this->client()->post('/api/messages/send', [
            'number' => $this->sanitizePhone($phone),
            'openTicket' => '0',
            'queueId' => '0',
            'body' => $message,
        ]);

        if (! $response->successful()) {
            return false;
        }

        return $response->json() ?? true;
    }

    public function sendImage(string $phone, string $url, ?string $caption = null): array|bool
    {
        return $this->sendText($phone, trim(($caption ?? '')."\n".$url));
    }

    public function sendFile(string $phone, string $url, ?string $filename = null): array|bool
    {
        return $this->sendText($phone, trim(($filename ?? '')."\n".$url));
    }

    public function sendButtons(string $phone, string $message, array $buttons): array|bool
    {
        $options = collect($buttons)
            ->values()
            ->map(fn (array $button, int $index): string => sprintf('%d - %s', $index + 1, $button['label']))
            ->implode("\n");

        return $this->sendText($phone, trim($message."\n\n".$options));
    }

    public function restart(): bool
    {
        return $this->client()->post('/api/whatsapp-restart', [
            'token' => $this->token,
        ])->successful();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentConnection(): ?array
    {
        $response = $this->client()->post('/api/whatsapp-status');

        if (! $response->successful()) {
            return null;
        }

        foreach ($response->json('whatsapps') ?? [] as $connection) {
            if (is_array($connection) && ($connection['token'] ?? null) === $this->token) {
                return $connection;
            }
        }

        return null;
    }

    private function sanitizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(20);
    }
}
