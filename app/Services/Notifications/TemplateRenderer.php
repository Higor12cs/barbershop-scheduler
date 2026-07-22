<?php

namespace App\Services\Notifications;

use App\Models\Appointment;

class TemplateRenderer
{
    public function render(?string $template, Appointment $appointment, array $extra = []): string
    {
        return strtr((string) $template, $this->placeholders($appointment, $extra));
    }

    private function placeholders(Appointment $appointment, array $extra): array
    {
        return [
            '{cliente}' => (string) $appointment->customer?->name,
            '{funcionario}' => (string) $appointment->employee?->name,
            '{servico}' => (string) $appointment->product?->name,
            '{data}' => $appointment->starts_at->format('d/m/Y'),
            '{hora}' => $appointment->starts_at->format('H:i'),
            '{estabelecimento}' => (string) (tenant('name') ?? ''),
            '{link_calendario}' => (string) ($extra['{link_calendario}'] ?? ''),
        ];
    }
}
