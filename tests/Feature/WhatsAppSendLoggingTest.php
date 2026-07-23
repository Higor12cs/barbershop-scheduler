<?php

use App\Services\WhatsApp\DiChatConnector;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('reports the http status and body when a send fails', function (): void {
    Http::fake([
        '*' => Http::response('invalid token', 401),
    ]);

    $connector = new DiChatConnector(['token' => 'secret']);

    $result = $connector->sendText('11999998888', 'Olá');

    expect($result)->toBeFalse()
        ->and($connector->lastError())->toContain('401')
        ->and($connector->lastError())->toContain('invalid token');
});

it('reports connection failures instead of throwing', function (): void {
    Http::fake(function (): void {
        throw new ConnectionException('cURL error 28: Operation timed out');
    });

    $connector = new DiChatConnector(['token' => 'secret']);

    $result = $connector->sendText('11999998888', 'Olá');

    expect($result)->toBeFalse()
        ->and($connector->lastError())
        ->toContain('conexão')
        ->toContain('timed out');
});

it('clears the last error and returns the response on success', function (): void {
    Http::fake([
        '*' => Http::response(['id' => 'msg_1', 'status' => 'queued'], 200),
    ]);

    $connector = new DiChatConnector(['token' => 'secret']);

    $result = $connector->sendText('11999998888', 'Olá');

    expect($result)->toBe(['id' => 'msg_1', 'status' => 'queued'])
        ->and($connector->lastError())->toBeNull();
});
