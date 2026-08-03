<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageOwnedCourses() ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'video_url' => ['required', 'url', 'max:2048'], 'duration' => ['nullable', 'integer', 'min:0', 'max:86400'], 'support_material' => ['nullable', 'url', 'max:2048'], 'order' => ['required', 'integer', 'min:0'], 'published' => ['sometimes', 'boolean']];
    }
}
