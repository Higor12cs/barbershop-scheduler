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
                $totals = ['created' => 0, 'skipped_existing' => 0, 'skipped_conflicts' => 0];

                Recurrence::query()
                    ->active()
                    ->with('product')
                    ->cursor()
                    ->each(function (Recurrence $recurrence) use ($generator, &$totals): void {
                        $summary = $generator->generate($recurrence);

                        $totals['created'] += $summary['created'];
                        $totals['skipped_existing'] += $summary['skipped_existing'];
                        $totals['skipped_conflicts'] += $summary['skipped_conflicts'];
                    });

                $this->line("{$tenant->name}: {$totals['created']} criados, {$totals['skipped_existing']} existentes, {$totals['skipped_conflicts']} conflitos.");
            });
        });

        return self::SUCCESS;
    }
}
