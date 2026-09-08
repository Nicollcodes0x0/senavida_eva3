<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackApiRequests;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;
use ReflectionClass;

class AdminStatsController extends Controller
{
    /**
     * Modelos que el contrato y el diseño del proyecto consideran
     * sensibles (datos clinicos o de configuracion). ChatMessage se
     * excluye a proposito: el contrato restringe que la auditoria
     * contenga el contenido integro de los mensajes, y no aparece en
     * la lista de acciones auditables del contrato.
     */
    private const AUDITABLE_MODELS = [
        \App\Models\HealthCenter::class,
        \App\Models\Organization::class,
        \App\Models\Patient::class,
        \App\Models\PatientContact::class,
        \App\Models\TemporaryAccessCode::class,
        \App\Models\Unit::class,
        \App\Models\User::class,
        \App\Models\Pictogram::class,
        \App\Models\PictogramCategory::class,
        \App\Models\SecuritySetting::class,
        \App\Models\Consent::class,
        \App\Models\MedicalSession::class,
    ];

    #[OA\Get(
        path: '/admin/stats',
        summary: 'Estadisticas resumen del panel de administracion',
        description: 'Exclusivo de admin_institucional. Las tres cifras se calculan en vivo, ninguna esta escrita a mano.',
        tags: ['Auditoria'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Estadisticas calculadas'),
            new OA\Response(response: 403, description: 'Sin permiso'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->role !== 'admin_institucional') {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FORBIDDEN_ROLE', 'message' => 'Solo un administrador institucional puede ver estas estadisticas.'],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'activeUsers'    => $this->countActiveUsers($admin->health_center_id),
                'apiRequests'    => Cache::get(TrackApiRequests::CACHE_KEY, 0),
                'auditCoverage'  => $this->calculateAuditCoverage(),
            ],
        ], 200);
    }

    private function countActiveUsers(?string $healthCenterId): int
    {
        return User::where('health_center_id', $healthCenterId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Calcula, en vivo y usando reflexion de PHP, que porcentaje de los
     * modelos sensibles tienen realmente el atributo #[ObservedBy(...)]
     * con AuditLogObserver. No es un numero fijo: si alguien agrega un
     * modelo sensible nuevo y olvida conectar el observador, este valor
     * baja automaticamente por debajo de 100.
     */
    private function calculateAuditCoverage(): int
    {
        $total = count(self::AUDITABLE_MODELS);
        $covered = 0;

        foreach (self::AUDITABLE_MODELS as $modelClass) {
            $reflection = new ReflectionClass($modelClass);
            $attributes = $reflection->getAttributes(ObservedBy::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if (in_array(\App\Observers\AuditLogObserver::class, $instance->classes, true)) {
                    $covered++;
                    break;
                }
            }
        }

        return $total > 0 ? (int) round(($covered / $total) * 100) : 0;
    }
}