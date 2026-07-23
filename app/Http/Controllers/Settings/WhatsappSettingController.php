<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\ConnectorManager;
use App\Services\WhatsApp\WhatsAppConnector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WhatsappSettingController extends Controller
{
    public function __construct(private ConnectorManager $connectors) {}

    public function show(): Response
    {
        $connector = $this->connectors->forCurrent();

        return Inertia::render('Settings/Whatsapp/Index', [
            'configured' => $connector !== null,
            'connection' => $connector !== null ? $this->connection($connector) : null,
        ]);
    }

    public function status(): JsonResponse
    {
        $connector = $this->connectors->forCurrent();

        if ($connector === null) {
            return response()->json(['configured' => false, 'connected' => false, 'phone' => null, 'qr' => null]);
        }

        return response()->json(['configured' => true, ...$this->connection($connector)]);
    }

    public function restart(): RedirectResponse
    {
        return $this->run(fn (WhatsAppConnector $connector) => $connector->restart(), 'Conexão reiniciada.', 'Não foi possível reiniciar a conexão.');
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $connector = $this->connectors->forCurrent();

        if ($connector === null) {
            return back()->with('error', 'Nenhum provedor de WhatsApp configurado.');
        }

        try {
            $delivered = $connector->sendText($this->formatPhone($data['phone']), $data['message']) !== false;
        } catch (Throwable) {
            $delivered = false;
        }

        if ($delivered) {
            return back()->with('success', 'Mensagem de teste enviada.');
        }

        return back()->with('error', $connector->lastError() ?? 'Não foi possível enviar a mensagem de teste.');
    }

    private function connection(WhatsAppConnector $connector): array
    {
        try {
            $status = $connector->connectionStatus();
            $qr = $status['connected'] ? null : $connector->getQrCode();

            return [
                'connected' => $status['connected'],
                'phone' => $status['phone'],
                'qr' => $qr,
            ];
        } catch (Throwable) {
            return ['connected' => false, 'phone' => null, 'qr' => null];
        }
    }

    private function run(callable $action, string $success, string $error): RedirectResponse
    {
        $connector = $this->connectors->forCurrent();

        if ($connector === null) {
            return back()->with('error', 'Nenhum provedor de WhatsApp configurado.');
        }

        try {
            $done = (bool) $action($connector);
        } catch (Throwable) {
            $done = false;
        }

        return $done ? back()->with('success', $success) : back()->with('error', $error);
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
