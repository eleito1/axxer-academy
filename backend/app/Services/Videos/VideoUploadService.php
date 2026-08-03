<?php

namespace App\Services\Videos;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

class VideoUploadService
{
    public function __construct(private readonly VideoStorage $storage) {}

    public function upload(UploadedFile $file, User $uploader, Course $course, Lesson $lesson): array
    {
        $extension = $this->extension($file);
        $directory = $this->directory($uploader, $course, $lesson);
        $filename = $this->filename($lesson->title, $extension);
        $path = trim($directory.'/'.$filename, '/');

        try {
            $path = $this->storage->putFileAs($directory, $file, $filename);
        } catch (Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }

        return [
            'video_provider' => 'hostinger',
            'video_path' => $path,
            'video_url' => $this->storage->url($path),
            'video_original_name' => $file->getClientOriginalName(),
            'video_size' => $file->getSize(),
            'video_extension' => $extension,
            'video_duration' => null,
            'video_uploaded_at' => now(),
        ];
    }

    public function delete(string $path): void
    {
        $this->storage->delete($path);
    }

    public function slug(string $title): string
    {
        return Str::limit(Str::slug($title) ?: 'aula', 150, '');
    }

    private function directory(User $uploader, Course $course, Lesson $lesson): string
    {
        $creatorId = $course->creator_id ?: $uploader->id;
        $base = trim((string) config('videos.storage.directory', ''), '/');
        $directory = "creator-{$creatorId}/curso-{$course->id}/aula-{$lesson->id}";

        return $base === '' ? $directory : "{$base}/{$directory}";
    }

    private function filename(string $title, string $extension): string
    {
        return $this->slug($title).'-'.Str::lower(Str::random(6)).'.'.$extension;
    }

    private function extension(UploadedFile $file): string
    {
        return Str::lower($file->getClientOriginalExtension() ?: $file->extension());
    }
}
