<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Product;
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
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : null;

        return ['title' => ['required', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('courses')->where(fn ($q) => $q->where('product_id', $productId))->ignore($course instanceof Course ? $course->id : null)], 'description' => ['nullable', 'string'], 'cover_image' => ['nullable', 'url', 'max:2048'], 'order' => ['required', 'integer', 'min:0'], 'published' => ['sometimes', 'boolean']];
    }
}
