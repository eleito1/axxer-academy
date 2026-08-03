<?php

namespace App\Services\Videos;

use Illuminate\Http\UploadedFile;
use LogicException;

class ExternalUrlStorage implements VideoStorage
{
    public function putFileAs(string $directory, UploadedFile $file, string $filename): string
    {
        throw new LogicException('URLs externas não recebem upload de arquivos.');
    }

    public function delete(string $path): void {}

    public function url(string $path): string
    {
        return $path;
    }
}
