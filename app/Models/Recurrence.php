<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Recurrence extends Model
{
    protected $fillable = [
        'customer_id',
        'employee_id',
        'product_id',
        'time',
        'interval_days',
        'starts_on',
        'ends_on',
        'notes',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'interval_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @param  Builder<Recurrence>  $query
     * @return Builder<Recurrence>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array<int, Carbon>
     */
    public function occurrencesBetween(Carbon $from, Carbon $to): array
    {
        $interval = max(1, (int) $this->interval_days);
        $from = $from->copy()->startOfDay();
        $limit = $to->copy()->startOfDay();

        if ($this->ends_on !== null && $this->ends_on->lt($limit)) {
            $limit = $this->ends_on->copy()->startOfDay();
        }

        $date = $this->starts_on->copy()->startOfDay();

        if ($date->lt($from)) {
            $diff = (int) $date->diffInDays($from);
            $remainder = $diff % $interval;
            $advance = $remainder === 0 ? 0 : $interval - $remainder;
            $date->addDays($diff + $advance);
        }

        $dates = [];

        while ($date->lte($limit)) {
            $dates[] = $date->copy();
            $date->addDays($interval);
        }

        return $dates;
    }
}
