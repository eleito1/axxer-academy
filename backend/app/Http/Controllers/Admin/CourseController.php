<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.courses.index', ['product' => $product, 'courses' => $product->courses()->withCount('modules')->get()]);
    }

    public function create(Product $product): View
    {
        $this->authorize('create', Course::class);

        return view('admin.courses.form', [
            'product' => $product,
            'course' => new Course,
        ]);
    }

    public function store(CourseRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('create', Course::class);
        $course = Course::create($request->validated() + ['product_id' => $product->id, 'published' => $request->boolean('published')]);

        return redirect()->route('admin.products.courses.index', $course->product_id)->with('success', 'Curso criado.');
    }

    public function edit(Product $product, Course $course): View
    {
        $this->authorize('update', $course);

        return view('admin.courses.form', [
            'product' => $product,
            'course' => $course,
        ]);
    }

    public function update(CourseRequest $request, Product $product, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $course->update($request->validated() + ['product_id' => $product->id, 'published' => $request->boolean('published')]);

        return redirect()->route('admin.products.courses.index', $course->product_id)->with('success', 'Curso atualizado.');
    }

    public function destroy(Product $product, Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $course->delete();

        return back()->with('success', 'Curso excluído.');
    }
}
