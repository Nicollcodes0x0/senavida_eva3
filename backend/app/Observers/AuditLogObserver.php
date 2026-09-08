<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\HealthCenter;
use App\Models\TemporaryAccessCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogObserver
{
    /**
     * Modelos que representan la ESTRUCTURA del sistema (no la operacion
     * clinica diaria). Modificarlos (no crearlos) se considera mas sensible.
     */
    private const STRUCTURAL_MODELS = [
        \App\Models\User::class,
        \App\Models\Organization::class,
        \App\Models\HealthCenter::class,
        \App\Models\Unit::class,
        \App\Models\SecuritySetting::class,
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $model->getOriginal());
    }

    protected function record(Model $model, string $action, array $changes): void
    {
        // Auth::id() falla si el autenticado es un Patient (no implementa
        // todo lo que la interfaz Authenticatable completa exige). Por eso
        // se obtiene el usuario de forma segura: si es un User real, se usa
        // su id; si es un Patient (o nadie), el evento queda sin usuario
        // asociado en user_id, pero se sigue registrando igual.
        $actor = Auth::user();
        $userId = $actor instanceof \App\Models\User ? $actor->id : null;

        AuditLog::create([
            'user_id'          => $userId,
            'health_center_id' => $this->resolveHealthCenterId($model),
            'action'           => $action,
            'severity'         => $this->resolveSeverity($model, $action, $changes),
            'auditable_type'   => $model->getMorphClass(),
            'auditable_id'     => $model->getKey(),
            'changes'          => $changes,
            'ip_address'       => Request::ip(),
        ]);
    }

    /**
     * Encuentra a que centro de salud pertenece este evento, probando
     * varias estrategias en orden. Si ninguna aplica, queda en null -
     * y por diseÃƒÂ±o, nadie que solo vea su propio centro vera ese evento.
     */
    private function resolveHealthCenterId(Model $model): ?string
    {
        // 1. El propio modelo tiene la columna (User, Unit, TemporaryAccessCode...)
        if (array_key_exists('health_center_id', $model->getAttributes())) {
            return $model->health_center_id;
        }

        // 2. El modelo ES un centro de salud
        if ($model instanceof HealthCenter) {
            return $model->id;
        }

        // 3. El modelo tiene una atencion medica de la cual heredar el centro
        if (method_exists($model, 'medicalSession') && $model->medicalSession) {
            return $model->medicalSession->health_center_id;
        }

        // 4. Ultimo recurso: el centro de quien esta haciendo la accion,
        // solo si es personal (User). Un Patient no tiene health_center_id
        // propio de la misma forma, asi que en ese caso queda en null.
        $actor = Auth::user();
        return $actor instanceof \App\Models\User ? $actor->health_center_id : null;
    }

    /**
     * Clasifica la severidad segun el tipo de evento. Criterio de negocio,
     * no un valor fijo: revocar un consentimiento es siempre critico (asi
     * lo exige el contrato); modificar estructura del sistema es warning;
     * el resto (catalogos operativos) es info.
     */
    private function resolveSeverity(Model $model, string $action, array $changes): string
    {
        if ($model instanceof Consent && ($changes['status'] ?? null) === 'revoked') {
            return 'critical';
        }

        if ($model instanceof TemporaryAccessCode && ($changes['status'] ?? null) === 'blocked') {
            return 'warning';
        }

        if ($action === 'updated' && in_array(get_class($model), self::STRUCTURAL_MODELS, true)) {
            return 'warning';
        }

        return 'info';
    }
}