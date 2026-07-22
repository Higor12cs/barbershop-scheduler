<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Adicionar ao Calendário — {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-surface-alt px-4 py-10">
            <div class="w-full max-w-md">
                <div class="mb-6 flex flex-col items-center gap-2 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                    </span>
                    <span class="text-lg font-semibold">{{ config('app.name') }}</span>
                </div>

                <div class="card">
                    <div class="card-body space-y-5 p-6 sm:p-8">
                        <div class="space-y-1 text-center">
                            <h1 class="text-xl font-semibold">Adicionar ao Calendário</h1>
                            <p class="text-sm text-secondary">Salve seu agendamento no aplicativo de calendário.</p>
                        </div>

                        <div class="space-y-2 rounded-xl border border-border bg-surface-alt p-4 text-sm">
                            <p class="font-semibold text-foreground">{{ $title }}</p>
                            <p class="text-secondary">{{ $when }}</p>
                            @if ($location)
                                <p class="text-secondary">{{ $location }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            <a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-block">
                                Google Agenda
                            </a>
                            <a href="{{ $icsUrl }}" class="btn btn-secondary btn-block">
                                Apple Calendar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
