<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function createFromAppointment(Appointment $appointment): ?Sale
    {
        if (Sale::query()->where('appointment_id', $appointment->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($appointment): Sale {
            $appointment->loadMissing('product');

            $total = (float) $appointment->price;

            $sale = Sale::create([
                'customer_id' => $appointment->customer_id,
                'employee_id' => $appointment->employee_id,
                'appointment_id' => $appointment->id,
                'total' => $total,
                'status' => 'completed',
                'sold_at' => $appointment->ends_at,
            ]);

            $sale->items()->create([
                'product_id' => $appointment->product_id,
                'description' => $appointment->product?->name ?? 'Serviço',
                'quantity' => 1,
                'unit_price' => $total,
                'total' => $total,
            ]);

            return $sale;
        });
    }

    public function deleteForAppointment(Appointment $appointment): void
    {
        $sale = Sale::query()->where('appointment_id', $appointment->id)->first();

        if ($sale === null) {
            return;
        }

        DB::transaction(function () use ($sale): void {
            $sale->items()->delete();
            $sale->delete();
        });
    }
}
