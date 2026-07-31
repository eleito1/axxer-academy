<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('lesson')) ?? false;
    }

    public function rules(): array
    {
        return ['last_seconds' => ['required', 'integer', 'min:0', 'max:86400'], 'completed' => ['sometimes', 'boolean']];
    }
}
