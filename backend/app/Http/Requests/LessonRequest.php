<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageOwnedCourses() ?? false;
    }

    public function rules(): array
    {
        $lesson = $this->route('lesson');
        $videoRequired = $lesson instanceof Lesson && $lesson->exists ? 'nullable' : 'required';
        $maxMegabytes = (int) config('videos.max_megabytes', 500);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video' => [
                $videoRequired,
                'file',
                'mimes:mp4,mov,webm',
                'mimetypes:video/mp4,video/quicktime,video/webm',
                'max:'.($maxMegabytes * 1024),
            ],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'support_material' => ['nullable', 'url', 'max:2048'],
            'order' => ['required', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'video.required' => 'Selecione um vídeo para a aula.',
            'video.file' => 'Envie um arquivo de vídeo válido.',
            'video.mimes' => 'O vídeo deve estar em MP4, MOV ou WEBM.',
            'video.mimetypes' => 'O vídeo deve ser um arquivo de vídeo válido em MP4, MOV ou WEBM.',
            'video.max' => 'O vídeo excede o limite configurado para upload.',
        ];
    }

    public function attributes(): array
    {
        return [
            'video' => 'vídeo',
        ];
    }
}
