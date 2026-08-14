<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Conserva quién envió el recurso a la papelera.
 *
 * La escritura ocurre después del borrado lógico porque `runSoftDelete` sólo
 * persiste `deleted_at` y `updated_at`; se usa el query builder para no disparar
 * el evento `updated` y evitar registros de auditoría duplicados.
 */
trait RecordsDeletedBy
{
    public static function bootRecordsDeletedBy(): void
    {
        static::deleted(function (self $model): void {
            if ($model->isForceDeleting()) {
                return;
            }

            $model->writeDeletedBy(Auth::id());
        });

        static::restored(function (self $model): void {
            $model->writeDeletedBy(null);
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    private function writeDeletedBy(?int $userId): void
    {
        if ($this->getAttribute('deleted_by') === $userId) {
            return;
        }

        $this->newQueryWithoutScopes()
            ->whereKey($this->getKey())
            ->toBase()
            ->update(['deleted_by' => $userId]);

        $this->setAttribute('deleted_by', $userId);
        $this->syncOriginalAttribute('deleted_by');
    }
}
