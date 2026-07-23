<?php

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class NubeStorageService
{
    public function disk(): Filesystem
    {
        return Storage::disk('nube');
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function read(string $path): string
    {
        return $this->disk()->get($path);
    }

    public function write(string $path, string $contents): bool
    {
        return $this->disk()->put($path, $contents);
    }

    public function delete(string $path): bool
    {
        return $this->disk()->delete($path);
    }
}
