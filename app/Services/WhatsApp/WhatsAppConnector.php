<?php

namespace App\Services\WhatsApp;

interface WhatsAppConnector
{
    public function connectionStatus(): array;

    public function getQrCode(): ?string;

    public function sendText(string $phone, string $message): array|bool;

    public function sendImage(string $phone, string $url, ?string $caption = null): array|bool;

    public function sendFile(string $phone, string $url, ?string $filename = null): array|bool;

    public function sendButtons(string $phone, string $message, array $buttons): array|bool;

    public function restart(): bool;

    /**
     * Human-readable reason for the most recent failed send, if any.
     */
    public function lastError(): ?string;
}
