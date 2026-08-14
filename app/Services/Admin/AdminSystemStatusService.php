<?php

namespace App\Services\Admin;

use App\Models\File;
use App\Services\Access\AccessApiService;
use App\Services\Access\Exceptions\AccessApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Throwable;

class AdminSystemStatusService
{
    private const PROBE_CACHE_KEY = 'admin.access_api.last_probe';

    private const PROBE_CACHE_SECONDS = 3600;

    public function __construct(
        private readonly AccessApiService $accessApi,
    ) {}

    /**
     * Límites de carga declarados en la aplicación y su alineación real con PHP.
     *
     * @return array<string, mixed>
     */
    public function uploads(): array
    {
        $applicationBytes = max(0, (int) config('nube.files.max_size_kb', 204800)) * 1024;
        $uploadMaxBytes = $this->iniBytes('upload_max_filesize');
        $postMaxBytes = $this->iniBytes('post_max_size');

        return [
            'max_size' => $this->formatBytes($applicationBytes),
            'max_size_bytes' => $applicationBytes,
            'php_upload_max' => $uploadMaxBytes === null
                ? 'No disponible'
                : $this->formatBytes($uploadMaxBytes),
            'php_post_max' => $postMaxBytes === null
                ? 'No disponible'
                : $this->formatBytes($postMaxBytes),
            'aligned' => $uploadMaxBytes !== null
                && $postMaxBytes !== null
                && $uploadMaxBytes >= $applicationBytes
                && $postMaxBytes >= $applicationBytes,
            'extensions' => array_values((array) config('nube.files.extensions', [])),
            'mime_types_count' => count((array) config('nube.files.mime_types', [])),
        ];
    }

    /**
     * Disco, raíz configurada y consumo. La raíz se expresa siempre relativa al
     * proyecto para no revelar la estructura del servidor.
     *
     * @return array<string, mixed>
     */
    public function storage(): array
    {
        $root = (string) config('filesystems.disks.nube.root');
        $totals = File::withTrashed()
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN size_bytes ELSE 0 END), 0) as active_bytes')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN size_bytes ELSE 0 END), 0) as trashed_bytes')
            ->firstOrFail();

        $activeBytes = (int) $totals->active_bytes;
        $trashedBytes = (int) $totals->trashed_bytes;
        $freeBytes = $this->freeBytes($root);
        $totalBytes = $this->totalBytes($root);

        return [
            'disk' => (string) config('filesystems.disks.nube.driver', 'local'),
            'disk_name' => 'nube',
            'root' => $this->relativeRoot($root),
            'visibility' => (string) config('filesystems.disks.nube.visibility', 'private'),
            'outside_public' => ! Str::startsWith(
                $this->normalize($root),
                $this->normalize(public_path()),
            ),
            'public_link_absent' => ! file_exists(public_path('storage')),
            'active_storage' => $this->formatBytes($activeBytes),
            'trashed_storage' => $this->formatBytes($trashedBytes),
            'used_storage' => $this->formatBytes($activeBytes + $trashedBytes),
            'free_storage' => $freeBytes === null ? 'No disponible' : $this->formatBytes($freeBytes),
            'total_storage' => $totalBytes === null ? 'No disponible' : $this->formatBytes($totalBytes),
            'used_percentage' => $totalBytes === null || $totalBytes === 0 || $freeBytes === null
                ? null
                : (int) round((($totalBytes - $freeBytes) / $totalBytes) * 100),
        ];
    }

    /**
     * Retención y purga de la papelera.
     *
     * @return array<string, mixed>
     */
    public function trash(): array
    {
        $retentionDays = max(1, (int) config('nube.trash_retention_days', 30));

        return [
            'retention_days' => $retentionDays,
            'purge_schedule' => 'Diaria a las 02:00',
            'purge_command' => 'files:purge-trash',
            'pending_purge' => File::onlyTrashed()
                ->where('deleted_at', '<=', now()->subDays($retentionDays))
                ->count(),
            'folders_purged_manually' => true,
        ];
    }

    /**
     * Estado del API de Accesos. `session_validated_at` es evidencia real: la
     * sesión sólo continúa activa si `/api/auth/me` respondió correctamente en
     * la última revalidación. La comprobación en vivo es explícita para no
     * lanzar peticiones externas en cada carga del panel.
     *
     * @return array<string, mixed>
     */
    public function accessApi(?int $sessionValidatedAt): array
    {
        $probe = Cache::get(self::PROBE_CACHE_KEY);
        $validatedAt = $sessionValidatedAt === null
            ? null
            : Carbon::createFromTimestamp($sessionValidatedAt);

        return [
            'host' => $this->host((string) config('services.access.url')),
            'timeout' => (int) config('services.access.timeout', 10),
            'session_check_interval' => (int) config('services.access.session_check_interval', 300),
            'system_key_configured' => filled(config('services.access.system_key')),
            'session_validated_at' => $validatedAt,
            'probe_state' => $probe['state'] ?? null,
            'probe_message' => $probe['message'] ?? null,
            'probe_at' => isset($probe['at']) ? Carbon::parse($probe['at']) : null,
        ];
    }

    /**
     * Comprobación en vivo contra el API usando el token de la sesión. No
     * almacena ni devuelve el token ni la clave del sistema.
     *
     * @return array{state: string, message: string}
     */
    public function probeAccessApi(?string $token): array
    {
        $result = match (true) {
            ! is_string($token) || $token === '' => [
                'state' => 'unknown',
                'message' => 'La sesión no tiene un token disponible para comprobar el API.',
            ],
            default => $this->runProbe($token),
        };

        Cache::put(
            self::PROBE_CACHE_KEY,
            [...$result, 'at' => now()->toIso8601String()],
            self::PROBE_CACHE_SECONDS,
        );

        return $result;
    }

    /**
     * @return array{state: string, message: string}
     */
    private function runProbe(string $token): array
    {
        try {
            $this->accessApi->currentUser($token);

            return [
                'state' => 'online',
                'message' => 'El API de Accesos respondió correctamente.',
            ];
        } catch (AccessApiException $exception) {
            return [
                'state' => 'degraded',
                'message' => 'El API de Accesos respondió con un error de sesión o autorización.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'state' => 'offline',
                'message' => 'No fue posible contactar al API de Accesos.',
            ];
        }
    }

    public function formatBytes(int $bytes): string
    {
        return Number::fileSize(max(0, $bytes), precision: 1);
    }

    private function iniBytes(string $directive): ?int
    {
        $value = ini_get($directive);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function freeBytes(string $root): ?int
    {
        if (! is_dir($root)) {
            return null;
        }

        $free = @disk_free_space($root);

        return is_float($free) || is_int($free) ? (int) $free : null;
    }

    private function totalBytes(string $root): ?int
    {
        if (! is_dir($root)) {
            return null;
        }

        $total = @disk_total_space($root);

        return is_float($total) || is_int($total) ? (int) $total : null;
    }

    private function relativeRoot(string $root): string
    {
        $normalized = $this->normalize($root);
        $base = $this->normalize(base_path()).'/';

        return Str::startsWith($normalized, $base)
            ? Str::after($normalized, $base)
            : 'Fuera del directorio del proyecto';
    }

    private function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'No configurado';
    }
}
