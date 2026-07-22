<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = Period::fromRequest($request);
        $search = trim((string) $request->string('search'));
        $employeeId = $request->integer('employee_id') ?: null;

        $query = Sale::query()
            ->whereBetween('sold_at', [$filters['from'], $filters['to']])
            ->when($employeeId !== null, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            });

        $total = (float) (clone $query)->sum('total');
        $count = (clone $query)->count();

        $sales = $query
            ->with(['customer:id,name', 'employee:id,name', 'items:id,sale_id,description,quantity'])
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sale $sale): array => [
                'id' => $sale->id,
                'sold_at' => $sale->sold_at->format('d/m/Y H:i'),
                'customer_name' => $sale->customer?->name,
                'employee_name' => $sale->employee?->name,
                'items_summary' => $this->itemsSummary($sale),
                'total' => (float) $sale->total,
                'from_appointment' => $sale->appointment_id !== null,
            ]);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'summary' => [
                'total' => $total,
                'count' => $count,
                'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            ],
            'filters' => [
                'period' => $filters['period'],
                'date_from' => $filters['from']->toDateString(),
                'date_to' => $filters['to']->toDateString(),
                'employee_id' => $employeeId,
                'search' => $search,
            ],
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Create', [
            'customers' => Customer::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone'])
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ])->all(),
            'employees' => $this->employeeOptions(),
            'products' => Product::query()
                ->where('active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'price'])
                ->map(fn (Product $product): array => [
                    'value' => $product->id,
                    'label' => $product->name,
                    'type_label' => $product->type->label(),
                    'price' => (float) $product->price,
                ])->all(),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $products = Product::query()
            ->whereIn('id', array_column($data['items'], 'product_id'))
            ->get()
            ->keyBy('id');

        $items = array_map(function (array $item) use ($products): array {
            $quantity = (int) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);

            return [
                'product_id' => (int) $item['product_id'],
                'description' => $products[(int) $item['product_id']]->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }, $data['items']);

        DB::transaction(function () use ($data, $items): void {
            $sale = Sale::create([
                'customer_id' => $data['customer_id'],
                'employee_id' => $data['employee_id'] ?? null,
                'total' => round(array_sum(array_column($items, 'total')), 2),
                'status' => 'completed',
                'sold_at' => Carbon::parse("{$data['date']} {$data['time']}"),
            ]);

            $sale->items()->createMany($items);
        });

        return to_route('sales.index')->with('success', 'Venda registrada com sucesso!');
    }

    public function show(Sale $sale): Response
    {
        $sale->load(['customer:id,name,phone', 'employee:id,name,color', 'items', 'appointment:id,starts_at']);

        return Inertia::render('Sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'sold_at' => $sale->sold_at->format('d/m/Y H:i'),
                'customer_name' => $sale->customer?->name,
                'customer_phone' => $sale->customer?->phone,
                'employee_name' => $sale->employee?->name,
                'employee_color' => $sale->employee?->color,
                'total' => (float) $sale->total,
                'from_appointment' => $sale->appointment_id !== null,
                'appointment_date' => $sale->appointment?->starts_at->toDateString(),
                'items' => $sale->items->map(fn (SaleItem $item): array => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ])->all(),
            ],
        ]);
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return to_route('sales.index')->with('success', 'Venda removida com sucesso!');
    }

    private function itemsSummary(Sale $sale): string
    {
        $descriptions = $sale->items
            ->map(fn (SaleItem $item): string => ((int) $item->quantity) > 1
                ? ((int) $item->quantity).'x '.$item->description
                : $item->description);

        $summary = $descriptions->take(2)->implode(', ');
        $remaining = $descriptions->count() - 2;

        return $remaining > 0 ? "{$summary} +{$remaining}" : $summary;
    }

    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Employee $employee): array => [
                'value' => $employee->id,
                'label' => $employee->name,
            ])->all();
    }
}
