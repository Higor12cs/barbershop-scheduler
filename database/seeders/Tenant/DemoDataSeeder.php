<?php

namespace Database\Seeders\Tenant;

use App\Enums\AppointmentStatus;
use App\Enums\ProductType;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Recurrence;
use App\Models\Sale;
use App\Services\RecurrenceGenerator;
use App\Services\SaleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'André Souza', 'phone' => '11987654321', 'email' => 'andre.souza@gmail.com', 'birth_date' => '1990-03-14'],
            ['name' => 'Bruno Carvalho', 'phone' => '11976543210', 'email' => 'bruno.carvalho@hotmail.com', 'birth_date' => '1985-07-22'],
            ['name' => 'Carlos Eduardo Lima', 'phone' => '11965432109', 'email' => 'cadu.lima@gmail.com', 'birth_date' => '1998-11-05'],
            ['name' => 'Diego Ferreira', 'phone' => '11954321098', 'email' => null, 'birth_date' => null],
            ['name' => 'Felipe Nogueira', 'phone' => '11943210987', 'email' => 'felipe.nogueira@outlook.com', 'birth_date' => '1993-01-30'],
            ['name' => 'Gustavo Ribeiro', 'phone' => '11932109876', 'email' => 'gustavo.ribeiro@gmail.com', 'birth_date' => '1988-09-17'],
            ['name' => 'Marcos Vinícius Alves', 'phone' => '11921098765', 'email' => null, 'birth_date' => '2000-05-08'],
            ['name' => 'Rafael Martins', 'phone' => '11910987654', 'email' => 'rafa.martins@gmail.com', 'birth_date' => '1995-12-02'],
        ];

        foreach ($customers as $customer) {
            Customer::create([...$customer, 'active' => true]);
        }

        $employees = [
            ['name' => 'João Barbosa', 'phone' => '11999887766', 'email' => 'joao@barbeariademo.com.br', 'color' => '#059669'],
            ['name' => 'Pedro Henrique Santos', 'phone' => '11988776655', 'email' => 'pedro@barbeariademo.com.br', 'color' => '#0284c7'],
            ['name' => 'Lucas Almeida', 'phone' => '11977665544', 'email' => 'lucas@barbeariademo.com.br', 'color' => '#d97706'],
        ];

        foreach ($employees as $employee) {
            Employee::create([...$employee, 'active' => true]);
        }

        $services = [
            ['name' => 'Corte Masculino', 'price' => 45.00, 'duration_minutes' => 30],
            ['name' => 'Barba', 'price' => 35.00, 'duration_minutes' => 20],
            ['name' => 'Corte + Barba', 'price' => 70.00, 'duration_minutes' => 45],
            ['name' => 'Sobrancelha', 'price' => 15.00, 'duration_minutes' => 10],
        ];

        foreach ($services as $service) {
            Product::create([
                ...$service,
                'type' => ProductType::Service,
                'active' => true,
            ]);
        }

        $products = [
            ['name' => 'Pomada Modeladora', 'price' => 55.00, 'cost' => 28.00],
            ['name' => 'Óleo para Barba', 'price' => 40.00, 'cost' => 19.50],
            ['name' => 'Shampoo Antiqueda', 'price' => 38.00, 'cost' => 17.00],
        ];

        foreach ($products as $product) {
            Product::create([
                ...$product,
                'type' => ProductType::Product,
                'active' => true,
            ]);
        }

        $this->seedAppointments();
        $this->seedRecurrences();
        $this->seedManualSales();
    }

    private function seedAppointments(): void
    {
        $customers = Customer::query()->orderBy('id')->get()->values();
        $employees = Employee::query()->orderBy('id')->get()->values();
        $services = Product::query()->where('type', ProductType::Service->value)->orderBy('id')->get()->values();

        $today = Carbon::today();
        $sales = app(SaleService::class);

        $specs = [
            [-2, '09:00', 0, 0, 0],
            [-2, '11:00', 1, 1, 2],
            [-2, '14:00', 2, 2, 1],
            [-1, '10:00', 0, 3, 2],
            [-1, '13:00', 1, 4, 0],
            [-1, '16:00', 2, 5, 3],
            [0, '08:00', 0, 6, 0],
            [1, '09:00', 0, 7, 1],
            [1, '11:00', 1, 0, 2],
            [1, '15:00', 2, 1, 0],
            [2, '10:00', 0, 2, 2],
            [2, '14:00', 1, 3, 0],
            [3, '11:00', 2, 4, 2],
            [3, '16:00', 0, 5, 0],
        ];

        $futureStatuses = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed];
        $futureIndex = 0;

        foreach ($specs as $index => [$dayOffset, $time, $employeeIndex, $customerIndex, $serviceIndex]) {
            $service = $services[$serviceIndex];
            [$hour, $minute] = array_map('intval', explode(':', $time));

            $startsAt = $today->copy()->addDays($dayOffset)->setTime($hour, $minute);
            $endsAt = $startsAt->copy()->addMinutes(max(5, (int) $service->duration_minutes));

            $status = match ($index) {
                2 => AppointmentStatus::NoShow,
                9 => AppointmentStatus::Cancelled,
                default => $startsAt->isPast()
                    ? AppointmentStatus::Completed
                    : $futureStatuses[$futureIndex++ % 2],
            };

            $appointment = Appointment::create([
                'customer_id' => $customers[$customerIndex]->id,
                'employee_id' => $employees[$employeeIndex]->id,
                'product_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status->value,
                'price' => $service->price,
                'origin' => 'manual',
            ]);

            if ($status === AppointmentStatus::Completed) {
                $sales->createFromAppointment($appointment);
            }
        }
    }

    private function seedRecurrences(): void
    {
        $customers = Customer::query()->orderBy('id')->get()->values();
        $employees = Employee::query()->orderBy('id')->get()->values();
        $services = Product::query()->where('type', ProductType::Service->value)->orderBy('id')->get()->values();

        $weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY);

        $recurrences = [
            [
                'customer_id' => $customers[0]->id,
                'employee_id' => $employees[0]->id,
                'product_id' => $services[0]->id,
                'time' => '10:00',
                'interval_days' => 7,
                'starts_on' => $weekStart->toDateString(),
                'ends_on' => null,
                'active' => true,
            ],
            [
                'customer_id' => $customers[1]->id,
                'employee_id' => $employees[1]->id,
                'product_id' => $services[2]->id,
                'time' => '15:00',
                'interval_days' => 14,
                'starts_on' => $weekStart->toDateString(),
                'ends_on' => null,
                'active' => true,
            ],
        ];

        $generator = app(RecurrenceGenerator::class);

        foreach ($recurrences as $data) {
            $recurrence = Recurrence::create($data);
            $generator->generate($recurrence);
        }
    }

    private function seedManualSales(): void
    {
        $customers = Customer::query()->orderBy('id')->get()->values();
        $employees = Employee::query()->orderBy('id')->get()->values();
        $products = Product::query()->orderBy('id')->get()->values();

        $today = Carbon::today();

        $specs = [
            [13, '10:30', 0, 2, [[4, 1]]],
            [12, '15:00', 1, 5, [[0, 1], [5, 1]]],
            [11, '11:15', 2, 7, [[4, 2]]],
            [9, '17:40', 0, 1, [[6, 1]]],
            [8, '09:20', 1, 3, [[2, 1], [4, 1]]],
            [7, '14:10', 2, 6, [[5, 1], [6, 1]]],
            [5, '16:30', 0, 0, [[1, 1], [5, 1]]],
            [4, '10:00', 2, 4, [[4, 1], [6, 2]]],
            [2, '12:45', 1, 2, [[3, 1]]],
            [1, '18:20', 0, 7, [[0, 1], [4, 1]]],
        ];

        foreach ($specs as [$daysAgo, $time, $employeeIndex, $customerIndex, $lines]) {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            $soldAt = $today->copy()->subDays($daysAgo)->setTime($hour, $minute);

            $items = array_map(function (array $line) use ($products): array {
                [$productIndex, $quantity] = $line;
                $product = $products[$productIndex];

                return [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => (float) $product->price,
                    'total' => round($quantity * (float) $product->price, 2),
                ];
            }, $lines);

            $sale = Sale::create([
                'customer_id' => $customers[$customerIndex]->id,
                'employee_id' => $employees[$employeeIndex]->id,
                'total' => round(array_sum(array_column($items, 'total')), 2),
                'status' => 'completed',
                'sold_at' => $soldAt,
            ]);

            $sale->items()->createMany($items);
        }
    }
}
