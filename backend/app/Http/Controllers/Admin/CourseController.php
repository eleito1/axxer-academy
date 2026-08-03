<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function mine(): View
    {
        $user = request()->user();
        $courses = $user->createdCourses()->with(['product', 'creator'])->withCount('modules')->orderBy('order')->orderBy('id')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.courses.mine', [
            'courses' => $courses,
            'products' => $products,
            'routePrefix' => 'creator',
        ]);
    }

    public function index(Product $product): View
    {
        $this->authorize('viewAny', Course::class);
        $courses = $product->courses()->with('creator')->withCount('modules');
        if (request()->user()->isCreator()) {
            $courses->ownedBy(request()->user());
        }

        return view('admin.courses.index', [
            'product' => $product,
            'courses' => $courses->get(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create(Product $product): View
    {
        $this->authorize('create', Course::class);

        return view('admin.courses.form', [
            'product' => $product,
            'course' => new Course,
            'creators' => $this->creators(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(CourseRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('create', Course::class);
        $data = $request->validated();
        $creatorId = $request->user()->isAdmin() ? ($data['creator_id'] ?? null) : $request->user()->id;
        unset($data['creator_id']);

        $course = $product->courses()->create($data + [
            'creator_id' => $creatorId,
            'published' => $request->boolean('published'),
        ]);

        return redirect()->route($this->routeName('products.courses.index'), $course->product_id)->with('success', 'Curso criado.');
    }

    public function edit(Product $product, Course $course): View
    {
        $this->authorize('update', $course);

        return view('admin.courses.form', [
            'product' => $product,
            'course' => $course,
            'creators' => $this->creators(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function update(CourseRequest $request, Product $product, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $data = $request->validated();
        if ($request->user()->isAdmin()) {
            $data['creator_id'] = $data['creator_id'] ?? null;
        } else {
            unset($data['creator_id']);
        }

        $course->update($data + ['product_id' => $product->id, 'published' => $request->boolean('published')]);

        return redirect()->route($this->routeName('products.courses.index'), $course->product_id)->with('success', 'Curso atualizado.');
    }

    public function destroy(Product $product, Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $course->delete();

        return back()->with('success', 'Curso excluído.');
    }

    private function routePrefix(): string
    {
        return request()->routeIs('creator.*') ? 'creator' : 'admin';
    }

    private function routeName(string $name): string
    {
        return $this->routePrefix().'.'.$name;
    }

    private function creators()
    {
        if (! request()->user()->isAdmin()) {
            return collect();
        }

        return User::query()->where('role', UserRole::Creator->value)->orderBy('name')->get();
    }
}
