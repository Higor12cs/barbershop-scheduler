<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiChatConnector implements WhatsAppConnector
{
    private string $baseUrl;

    private string $token;

    private ?string $lastError = null;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api11.dichat.com.br'), '/');
        $this->token = (string) ($config['token'] ?? '');
    }

    public function lastError(): ?string
    {
        return $this->lastError;
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
        $number = $this->sanitizePhone($phone);

        return $this->send('text', $number, '/api/messages/send', [
            'number' => $number,
            'openTicket' => '0',
            'queueId' => '0',
            'body' => $message,
        ]);
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
     * Send a payload to the provider, logging the outcome and capturing the
     * failure reason so callers can surface why a message was not delivered.
     *
     * @param  array<string, mixed>  $payload
     */
    private function send(string $kind, string $phone, string $endpoint, array $payload): array|bool
    {
        $this->lastError = null;

        $context = [
            'tenant' => tenant('id'),
            'kind' => $kind,
            'phone' => $phone,
        ];

        try {
            $response = $this->client()->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            $this->lastError = 'Falha de conexão com o provedor: '.$exception->getMessage();

            Log::error('[WhatsApp] Send failed (connection)', [
                ...$context,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            $this->lastError = sprintf('Provedor respondeu HTTP %d: %s', $response->status(), Str::limit(trim((string) $response->body()), 300));

            Log::warning('[WhatsApp] Send failed', [
                ...$context,
                'status' => $response->status(),
                'body' => Str::limit(trim((string) $response->body()), 500),
            ]);

            return false;
        }

        Log::info('[WhatsApp] Message sent', $context);

        return $response->json() ?? true;
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
