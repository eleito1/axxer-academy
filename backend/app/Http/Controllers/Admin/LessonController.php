<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Services\Videos\VideoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class LessonController extends Controller
{
    public function index(Product $product, Course $course, Module $module): View
    {
        $this->authorize('update', $module);

        return view('admin.lessons.index', ['product' => $product, 'course' => $course, 'module' => $module, 'lessons' => $module->lessons, 'routePrefix' => $this->routePrefix()]);
    }

    public function create(Product $product, Course $course, Module $module): View
    {
        $this->authorize('update', $module);

        return view('admin.lessons.form', ['product' => $product, 'course' => $course, 'module' => $module, 'lesson' => new Lesson, 'routePrefix' => $this->routePrefix()]);
    }

    public function store(LessonRequest $request, Product $product, Course $course, Module $module, VideoUploadService $videos): RedirectResponse
    {
        $this->authorize('update', $module);
        $uploaded = null;

        try {
            DB::transaction(function () use (&$uploaded, $request, $course, $module, $videos): void {
                $lesson = $module->lessons()->create($this->lessonData($request) + ['video_url' => 'about:blank']);
                $uploaded = $videos->upload($request->file('video'), $request->user(), $course, $lesson);
                $lesson->update($uploaded);
            });
        } catch (Throwable $exception) {
            if (is_array($uploaded) && ! empty($uploaded['video_path'])) {
                $videos->delete($uploaded['video_path']);
            }

            throw $exception;
        }

        return redirect()->route($this->routeName('products.courses.modules.lessons.index'), [$product, $course, $module])->with('success', 'Aula criada.');
    }

    public function edit(Product $product, Course $course, Module $module, Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        return view('admin.lessons.form', compact('product', 'course', 'module', 'lesson') + ['routePrefix' => $this->routePrefix()]);
    }

    public function update(LessonRequest $request, Product $product, Course $course, Module $module, Lesson $lesson, VideoUploadService $videos): RedirectResponse
    {
        $this->authorize('update', $lesson);
        $data = $this->lessonData($request);
        $oldPath = $lesson->video_provider === 'hostinger' ? $lesson->video_path : null;
        $uploaded = null;

        if ($request->hasFile('video')) {
            try {
                $uploaded = $videos->upload($request->file('video'), $request->user(), $course, $lesson);
                $data += $uploaded;
                $lesson->update($data);
            } catch (Throwable $exception) {
                if (is_array($uploaded) && ! empty($uploaded['video_path'])) {
                    $videos->delete($uploaded['video_path']);
                }

                throw $exception;
            }

            if ($oldPath && $oldPath !== $lesson->video_path) {
                $videos->delete($oldPath);
            }

            return redirect()->route($this->routeName('products.courses.modules.lessons.index'), [$product, $course, $module])->with('success', 'Aula atualizada.');
        }

        $lesson->update($data);

        return redirect()->route($this->routeName('products.courses.modules.lessons.index'), [$product, $course, $module])->with('success', 'Aula atualizada.');
    }

    public function destroy(Product $product, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);
        $lesson->delete();

        return back()->with('success', 'Aula excluída.');
    }

    private function routePrefix(): string
    {
        return request()->routeIs('creator.*') ? 'creator' : 'admin';
    }

    private function routeName(string $name): string
    {
        return $this->routePrefix().'.'.$name;
    }

    private function lessonData(LessonRequest $request): array
    {
        return Arr::except($request->validated(), ['video']) + ['published' => $request->boolean('published')];
    }
}
