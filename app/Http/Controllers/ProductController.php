<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $type = $request->string('type')->toString();
        $type = in_array($type, ProductType::values(), true) ? $type : '';

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($type !== '', function ($query) use ($type): void {
                $query->where('type', $type);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type->value,
                'type_label' => $product->type->label(),
                'price' => (float) $product->price,
                'duration_minutes' => $product->duration_minutes,
                'active' => $product->active,
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => ['search' => $search, 'type' => $type],
            'types' => ProductType::options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'types' => ProductType::options(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return to_route('products.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type->value,
                'price' => (float) $product->price,
                'cost' => $product->cost !== null ? (float) $product->cost : null,
                'duration_minutes' => $product->duration_minutes,
                'description' => $product->description,
                'active' => $product->active,
            ],
            'types' => ProductType::options(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('products.index')->with('success', 'Produto removido com sucesso!');
    }
}
