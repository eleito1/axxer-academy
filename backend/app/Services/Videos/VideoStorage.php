<?php

namespace App\Services\Videos;

use Illuminate\Http\UploadedFile;

interface VideoStorage
{
    public function putFileAs(string $directory, UploadedFile $file, string $filename): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
