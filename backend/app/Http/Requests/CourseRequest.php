<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $course = $this->route('course');

        return ['product_id' => ['required', 'exists:products,id'], 'title' => ['required', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('courses')->where(fn ($q) => $q->where('product_id', $this->integer('product_id')))->ignore($course instanceof Course ? $course->id : null)], 'description' => ['nullable', 'string'], 'cover_image' => ['nullable', 'url', 'max:2048'], 'order' => ['required', 'integer', 'min:0'], 'published' => ['sometimes', 'boolean']];
    }
}
