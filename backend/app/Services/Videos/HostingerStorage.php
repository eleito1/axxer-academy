<?php

namespace App\Services\Videos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HostingerStorage implements VideoStorage
{
    public function __construct(private readonly ?string $disk = null) {}

    public function putFileAs(string $directory, UploadedFile $file, string $filename): string
    {
        $directory = trim($directory, '/');
        $path = trim($directory.'/'.$filename, '/');
        $this->assertConfigured();
        $this->assertSafePath($path);

        Storage::disk($this->disk())->putFileAs($directory, $file, $filename);

        return $path;
    }

    public function delete(string $path): void
    {
        if ($path === '') {
            return;
        }

        $this->assertConfigured();
        $this->assertSafePath($path);
        Storage::disk($this->disk())->delete($path);
    }

    public function url(string $path): string
    {
        $this->assertConfigured();
        $this->assertSafePath($path);
        $baseUrl = config("filesystems.disks.{$this->disk()}.url");

        return rtrim((string) $baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function disk(): string
    {
        return $this->disk ?? config('videos.storage.disk', 'video_public');
    }

    private function assertConfigured(): void
    {
        $root = config("filesystems.disks.{$this->disk()}.root");
        $url = config("filesystems.disks.{$this->disk()}.url");

        if (! is_string($root) || trim($root) === '') {
            throw new RuntimeException('Configure VIDEO_STORAGE_PATH antes de enviar vídeos.');
        }

        if (! is_string($url) || trim($url) === '') {
            throw new RuntimeException('Configure VIDEO_PUBLIC_URL antes de enviar vídeos.');
        }
    }

    private function assertSafePath(string $path): void
    {
        if (str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, "\0")) {
            throw new RuntimeException('Caminho de vídeo inválido.');
        }
    }
}
