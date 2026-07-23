<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ScheduleBlock extends Model
{
    protected $fillable = [
        'employee_id',
        'starts_at',
        'ends_at',
        'all_day',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Blocks that touch the interval, including the ones that apply to every
     * employee.
     *
     * @param  Builder<ScheduleBlock>  $query
     * @return Builder<ScheduleBlock>
     */
    public function scopeOverlapping(Builder $query, ?int $employeeId, Carbon $start, Carbon $end): Builder
    {
        return $query->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($employeeId !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query->whereNull('employee_id')->orWhere('employee_id', $employeeId),
            ));
    }

    public function label(): string
    {
        return $this->reason ?: 'Período Bloqueado';
    }
}
