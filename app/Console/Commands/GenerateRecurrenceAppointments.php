<?php

namespace App\Console\Commands;

use App\Models\Recurrence;
use App\Models\Tenant;
use App\Services\RecurrenceGenerator;
use Illuminate\Console\Command;

class GenerateRecurrenceAppointments extends Command
{
    protected $signature = 'recurrences:generate';

    protected $description = 'Generate upcoming appointments for active recurrences across all tenants';

    public function handle(RecurrenceGenerator $generator): int
    {
        Tenant::query()->where('active', true)->cursor()->each(function (Tenant $tenant) use ($generator): void {
            $tenant->run(function () use ($tenant, $generator): void {
                $totals = RecurrenceGenerator::emptySummary();

                Recurrence::query()
                    ->active()
                    ->with('product')
                    ->cursor()
                    ->each(function (Recurrence $recurrence) use ($generator, &$totals): void {
                        foreach ($generator->generate($recurrence) as $key => $count) {
                            $totals[$key] += $count;
                        }
                    });

                $this->line("{$tenant->name}: {$totals['created']} criados, {$totals['skipped_existing']} existentes, {$totals['skipped_conflicts']} conflitos, {$totals['skipped_unavailable']} indisponíveis.");
            });
        });

        return self::SUCCESS;
    }
}
