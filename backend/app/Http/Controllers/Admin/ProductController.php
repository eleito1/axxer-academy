<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', ['products' => Product::query()->withCount('courses')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', ['product' => new Product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        Product::create($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.products.index')->with('success', 'Produto criado.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.form', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $product->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.products.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return back()->with('success', 'Produto excluído.');
    }
}
