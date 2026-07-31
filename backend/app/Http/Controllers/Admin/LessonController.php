<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Product $product, Course $course, Module $module): View
    {
        $this->authorize('update', $module);

        return view('admin.lessons.index', ['product' => $product, 'course' => $course, 'module' => $module, 'lessons' => $module->lessons]);
    }

    public function create(Product $product, Course $course, Module $module): View
    {
        $this->authorize('create', Lesson::class);

        return view('admin.lessons.form', ['product' => $product, 'course' => $course, 'module' => $module, 'lesson' => new Lesson]);
    }

    public function store(LessonRequest $request, Product $product, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('create', Lesson::class);
        $module->lessons()->create($request->validated() + ['published' => $request->boolean('published')]);

        return redirect()->route('admin.products.courses.modules.lessons.index', [$product, $course, $module])->with('success', 'Aula criada.');
    }

    public function edit(Product $product, Course $course, Module $module, Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        return view('admin.lessons.form', compact('product', 'course', 'module', 'lesson'));
    }

    public function update(LessonRequest $request, Product $product, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);
        $lesson->update($request->validated() + ['published' => $request->boolean('published')]);

        return redirect()->route('admin.products.courses.modules.lessons.index', [$product, $course, $module])->with('success', 'Aula atualizada.');
    }

    public function destroy(Product $product, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);
        $lesson->delete();

        return back()->with('success', 'Aula excluída.');
    }
}
