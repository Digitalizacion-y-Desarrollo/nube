<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Guarda las fotos de perfil en el disco privado `nube`, fuera de `public`.
 * Nunca se expone la ruta física: la imagen se sirve por controlador.
 */
class AvatarStorageService
{
    private const DISK = 'nube';

    public function store(User $user, UploadedFile $upload): string
    {
        $previousPath = $user->avatar_path;
        $extension = strtolower($upload->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().($extension === '' ? '' : ".{$extension}");
        $path = Storage::disk(self::DISK)->putFileAs(
            $this->directoryFor($user),
            $upload,
            $storedName,
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('No fue posible guardar la foto de perfil.');
        }

        try {
            $user->avatar_path = $path;
            $user->save();
        } catch (Throwable $exception) {
            // Compensación: si falla el registro, no se conserva la imagen nueva.
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }

        $this->deletePhysical($previousPath);

        return $path;
    }

    public function delete(User $user): void
    {
        $path = $user->avatar_path;

        $user->avatar_path = null;
        $user->save();

        $this->deletePhysical($path);
    }

    public function exists(User $user): bool
    {
        return $user->hasAvatar()
            && Storage::disk(self::DISK)->exists((string) $user->avatar_path);
    }

    public function contents(User $user): string
    {
        return (string) Storage::disk(self::DISK)->get((string) $user->avatar_path);
    }

    public function mimeType(User $user): string
    {
        $extension = strtolower(pathinfo((string) $user->avatar_path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            default => 'image/jpeg',
        };
    }

    public function disk(): string
    {
        return self::DISK;
    }

    private function directoryFor(User $user): string
    {
        return "perfiles/{$user->id}";
    }

    private function deletePhysical(?string $path): void
    {
        if (is_string($path) && $path !== '' && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
