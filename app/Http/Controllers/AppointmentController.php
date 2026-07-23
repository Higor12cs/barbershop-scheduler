<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationType;
use App\Enums\ProductType;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\NotificationSetting;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\AvailabilityService;
use App\Services\Notifications\AppointmentNotifier;
use App\Services\SaleService;
use App\Support\WorkingHours;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function index(Request $request): Response
    {
        $view = $request->string('view')->toString();
        $view = in_array($view, ['day', 'week'], true) ? $view : 'day';

        $date = ($request->date('date') ?? now())->startOfDay();
        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        if ($view === 'week') {
            $rangeStart = $date->copy()->startOfWeek(Carbon::MONDAY);
            $rangeEnd = $rangeStart->copy()->addDays(7);
        } else {
            $rangeStart = $date->copy();
            $rangeEnd = $date->copy()->addDay();
        }

        $appointments = Appointment::query()
            ->where('starts_at', '>=', $rangeStart)
            ->where('starts_at', '<', $rangeEnd)
            ->with(['customer:id,name,phone', 'employee:id,name,color', 'product:id,name,duration_minutes', 'sale:id,appointment_id', 'notifications'])
            ->orderBy('starts_at')
            ->get();

        $employees = Employee::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $dates = [];

        for ($day = $rangeStart->copy(); $day->lessThan($rangeEnd); $day->addDay()) {
            $dates[] = $day->toDateString();
        }

        $employeeIds = $employees->pluck('id')->all();
        $settings = $this->availability->gridSettings($employeeIds, $this->occupiedRanges($appointments));

        return Inertia::render('Appointments/Index', [
            'view' => $view,
            'date' => $date->toDateString(),
            'today' => now()->toDateString(),
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->copy()->subDay()->toDateString(),
            'selectedEmployeeId' => $selectedEmployeeId,
            'settings' => $settings,
            'employees' => $employees
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'color' => $employee->color,
                ])->all(),
            'availability' => $this->availability->calendar(
                $employeeIds,
                $dates,
                $settings['start_hour'] * 60,
                $settings['end_hour'] * 60,
            ),
            'services' => Product::query()
                ->where('active', true)
                ->where('type', ProductType::Service->value)
                ->orderBy('name')
                ->get(['id', 'name', 'duration_minutes', 'price'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'duration_minutes' => (int) $product->duration_minutes,
                    'price' => (float) $product->price,
                ])->all(),
            'customers' => Customer::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'birth_date'])
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'birth_date' => $customer->birth_date?->format('Y-m-d'),
                ])->all(),
            'appointments' => $appointments->map(fn (Appointment $appointment): array => $this->serialize($appointment))->all(),
            'statuses' => AppointmentStatus::options(),
            'whatsappReady' => tenant() instanceof Tenant && tenant()->hasWhatsAppProvider(),
            'bookingNotifyEnabled' => NotificationSetting::current()->booking_enabled,
            'messageTypes' => array_map(
                fn (NotificationType $type): array => ['value' => $type->value, 'label' => $type->label()],
                NotificationType::cases(),
            ),
        ]);
    }

    public function store(AppointmentRequest $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validated();

        $service = Product::query()->findOrFail($data['product_id']);
        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = $startsAt->copy()->addMinutes(max(5, (int) $service->duration_minutes));

        $this->guardAgainstUnavailability((int) $data['employee_id'], $startsAt, $endsAt, (bool) ($data['force'] ?? false));

        $appointment = DB::transaction(function () use ($data, $startsAt, $endsAt): Appointment {
            $this->guardAgainstOverlap((int) $data['employee_id'], $startsAt, $endsAt);

            return Appointment::create([
                'customer_id' => $data['customer_id'],
                'employee_id' => $data['employee_id'],
                'product_id' => $data['product_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Scheduled->value,
                'price' => $data['price'],
                'notes' => $data['notes'] ?? null,
                'origin' => 'manual',
            ]);
        });

        $appointment->loadMissing('customer:id,name,phone');

        return back()
            ->with('success', 'Agendamento criado com sucesso.')
            ->with('created_appointment', [
                'id' => $appointment->id,
                'customer_name' => $appointment->customer?->name,
                'has_phone' => filled($appointment->customer?->phone),
            ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validated();

        $service = Product::query()->findOrFail($data['product_id']);
        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = $startsAt->copy()->addMinutes(max(5, (int) $service->duration_minutes));

        $this->guardAgainstUnavailability((int) $data['employee_id'], $startsAt, $endsAt, (bool) ($data['force'] ?? false));

        DB::transaction(function () use ($appointment, $data, $startsAt, $endsAt): void {
            $this->guardAgainstOverlap((int) $data['employee_id'], $startsAt, $endsAt, $appointment->id);

            $appointment->update([
                'customer_id' => $data['customer_id'],
                'employee_id' => $data['employee_id'],
                'product_id' => $data['product_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'price' => $data['price'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Agendamento atualizado com sucesso.');
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'force' => ['boolean'],
        ]);

        $duration = (int) $appointment->starts_at->diffInMinutes($appointment->ends_at);
        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = $startsAt->copy()->addMinutes(max(5, $duration));

        $this->guardAgainstUnavailability((int) $data['employee_id'], $startsAt, $endsAt, (bool) ($data['force'] ?? false));

        $rescheduled = DB::transaction(function () use ($appointment, $data, $startsAt, $endsAt): bool {
            if (Appointment::query()->overlapping((int) $data['employee_id'], $startsAt, $endsAt, $appointment->id)->lockForUpdate()->exists()) {
                return false;
            }

            $appointment->update([
                'employee_id' => $data['employee_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            return true;
        });

        if (! $rescheduled) {
            return back()->with('error', 'Este horário conflita com outro agendamento do funcionário.');
        }

        return back()->with('success', 'Agendamento remarcado com sucesso.');
    }

    public function status(Request $request, Appointment $appointment, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
        ]);

        $status = AppointmentStatus::from($data['status']);
        $wasCompleted = $appointment->status === AppointmentStatus::Completed;

        $appointment->update(['status' => $status->value]);

        if ($status === AppointmentStatus::Completed) {
            $sales->createFromAppointment($appointment);

            return back()->with('success', 'Atendimento finalizado e venda gerada.');
        }

        if ($wasCompleted) {
            $sales->deleteForAppointment($appointment);

            return back()->with('success', 'Agendamento reaberto e venda excluída.');
        }

        return back()->with('success', 'Status do agendamento atualizado.');
    }

    public function notify(Request $request, Appointment $appointment, AppointmentNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(NotificationType::class)],
        ]);

        if (! tenant() instanceof Tenant || ! tenant()->hasWhatsAppProvider()) {
            return back()->with('error', 'Nenhum provedor de WhatsApp configurado.');
        }

        $type = NotificationType::from($data['type']);
        $notification = $notifier->deliver($appointment, $type, force: true);

        if ($notification === null || $notification->status !== AppointmentNotification::STATUS_SENT) {
            return back()->with('error', 'Não foi possível enviar a mensagem pelo WhatsApp.');
        }

        return back()->with('success', $type->label().' enviada com sucesso.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return back()->with('success', 'Agendamento removido com sucesso.');
    }

    private function guardAgainstOverlap(int $employeeId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): void
    {
        $conflict = Appointment::query()
            ->overlapping($employeeId, $startsAt, $endsAt, $ignoreId)
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'Este horário conflita com outro agendamento do funcionário.',
            ]);
        }
    }

    /**
     * Minute ranges taken by the listed appointments, so the grid never hides
     * one that sits outside the configured shifts.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return array<int, array{start: int, end: int}>
     */
    private function occupiedRanges(Collection $appointments): array
    {
        return $appointments->map(fn (Appointment $appointment): array => [
            'start' => $appointment->starts_at->hour * 60 + $appointment->starts_at->minute,
            'end' => $appointment->ends_at->isSameDay($appointment->starts_at)
                ? $appointment->ends_at->hour * 60 + $appointment->ends_at->minute
                : WorkingHours::MINUTES_IN_DAY,
        ])->all();
    }

    /**
     * Rejects slots outside the employee's working hours or inside a schedule
     * block. Reported under the dedicated `availability` key so the client can
     * offer to send the request again with `force`.
     */
    private function guardAgainstUnavailability(int $employeeId, Carbon $startsAt, Carbon $endsAt, bool $force): void
    {
        if ($force) {
            return;
        }

        $reason = $this->availability->reasonFor($employeeId, $startsAt, $endsAt);

        if ($reason !== null) {
            throw ValidationException::withMessages(['availability' => $reason]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Appointment $appointment): array
    {
        $startMinutes = $appointment->starts_at->hour * 60 + $appointment->starts_at->minute;
        $duration = (int) $appointment->starts_at->diffInMinutes($appointment->ends_at);

        return [
            'id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'customer_name' => $appointment->customer?->name,
            'customer_phone' => $appointment->customer?->phone,
            'employee_id' => $appointment->employee_id,
            'employee_name' => $appointment->employee?->name,
            'employee_color' => $appointment->employee?->color,
            'product_id' => $appointment->product_id,
            'product_name' => $appointment->product?->name,
            'date' => $appointment->starts_at->toDateString(),
            'start_time' => $appointment->starts_at->format('H:i'),
            'end_time' => $appointment->ends_at->format('H:i'),
            'start_minutes' => $startMinutes,
            'duration_minutes' => $duration,
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'price' => (float) $appointment->price,
            'notes' => $appointment->notes,
            'origin' => $appointment->origin,
            'has_sale' => $appointment->sale !== null,
            'notifications' => $appointment->notifications
                ->sortBy('created_at')
                ->map(fn (AppointmentNotification $notification): array => [
                    'type' => $notification->type->value,
                    'type_label' => $notification->type->label(),
                    'status' => $notification->status,
                    'sent_at' => $notification->sent_at?->format('d/m/Y H:i'),
                    'error' => $notification->error,
                ])
                ->values()
                ->all(),
        ];
    }
}
