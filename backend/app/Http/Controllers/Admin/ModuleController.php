<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModuleRequest;
use App\Models\Course;
use App\Models\Module;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Product $product, Course $course): View
    {
        $this->authorize('update', $course);

        return view('admin.modules.index', ['product' => $product, 'course' => $course, 'modules' => $course->modules()->withCount('lessons')->get()]);
    }

    public function create(Product $product, Course $course): View
    {
        $this->authorize('create', Module::class);

        return view('admin.modules.form', ['product' => $product, 'course' => $course, 'module' => new Module]);
    }

    public function store(ModuleRequest $request, Product $product, Course $course): RedirectResponse
    {
        $this->authorize('create', Module::class);
        $course->modules()->create($request->validated());

        return redirect()->route('admin.products.courses.modules.index', [$product, $course])->with('success', 'Módulo criado.');
    }

    public function edit(Product $product, Course $course, Module $module): View
    {
        $this->authorize('update', $module);

        return view('admin.modules.form', compact('product', 'course', 'module'));
    }

    public function update(ModuleRequest $request, Product $product, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);
        $module->update($request->validated());

        return redirect()->route('admin.products.courses.modules.index', [$product, $course])->with('success', 'Módulo atualizado.');
    }

    public function destroy(Product $product, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('delete', $module);
        $module->delete();

        return back()->with('success', 'Módulo excluído.');
    }
}
