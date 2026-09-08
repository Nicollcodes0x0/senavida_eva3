<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuditLogController extends Controller
{
    private const SORTABLE = [
        'occurredAt' => 'created_at',
        'severity'   => 'severity',
        'action'     => 'action',
    ];

    #[OA\Get(
        path: '/audit-logs',
        summary: 'Consultar la bitacora de auditoria del propio centro',
        description: 'Exclusivo de admin_institucional. Solo muestra eventos del propio centro de salud.',
        tags: ['Auditoria'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'severity', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['info', 'warning', 'critical'])),
            new OA\Parameter(name: 'userId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'occurredAtFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'occurredAtTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: '-occurredAt')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de eventos de auditoria'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 422, description: 'Rango de fechas invalido'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $admin = $request->user();

        // Filtro implicito, siempre activo: nunca se puede consultar la
        // bitacora de otro centro, ni siquiera construyendo el parametro a mano.
        $query = AuditLog::query()->where('health_center_id', $admin->health_center_id);

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }
        if ($request->filled('userId')) {
            $query->where('user_id', $request->query('userId'));
        }

        if ($request->filled('occurredAtFrom') || $request->filled('occurredAtTo')) {
            Validator::make($request->only('occurredAtFrom', 'occurredAtTo'), [
                'occurredAtFrom' => ['sometimes', 'date'],
                'occurredAtTo'   => ['sometimes', 'date'],
            ])->validate();

            if ($request->filled('occurredAtFrom') && $request->filled('occurredAtTo')
                && $request->query('occurredAtFrom') > $request->query('occurredAtTo')) {
                abort(422, 'occurredAtFrom no puede ser posterior a occurredAtTo.');
            }

            if ($request->filled('occurredAtFrom')) {
                $query->where('created_at', '>=', $request->query('occurredAtFrom'));
            }
            if ($request->filled('occurredAtTo')) {
                $query->where('created_at', '<=', $request->query('occurredAtTo'));
            }
        }

        $this->applySort($query, $request->query('sort'));

        $perPage = min(max((int) $request->query('perPage', 25), 1), 100);
        $paginated = $query->paginate($perPage);

        $this->recordSelfAudit($admin, 'viewed_audit_log');

        return response()->json([
            'success' => true,
            'data'    => collect($paginated->items())->map(fn ($log) => $this->toArray($log)),
            'meta'    => [
                'pagination' => [
                    'total'       => $paginated->total(),
                    'count'       => $paginated->count(),
                    'perPage'     => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'lastPage'    => $paginated->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * El contrato exige que CONSULTAR la bitacora tambien quede auditado
     * (SS13.7). Como leer no dispara ningun evento de Eloquent, el
     * AuditLogObserver nunca se entera por si solo - se registra el
     * evento a mano aqui, directamente sobre AuditLog.
     */
    private function recordSelfAudit($admin, string $action): void
    {
        AuditLog::create([
            'user_id'          => $admin->id,
            'health_center_id' => $admin->health_center_id,
            'action'           => $action,
            'severity'         => 'info',
            'auditable_type'   => null,
            'auditable_id'     => null,
            'changes'          => null,
            'ip_address'       => request()->ip(),
        ]);
    }

    #[OA\Post(
        path: '/audit-logs/export',
        summary: 'Exportar la bitacora de auditoria con firma HMAC',
        description: 'Exclusivo de admin_institucional. Genera un JSON firmado (HMAC-SHA256) que demuestra que el archivo no fue alterado despues de generarse. La firma con certificado digital queda para una fase posterior (decision D-30).',
        tags: ['Auditoria'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Exportacion firmada generada correctamente'),
            new OA\Response(response: 403, description: 'Sin permiso'),
        ]
    )]
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $admin = $request->user();

        // La exportacion no pagina: entrega todo el conjunto filtrado.
        // Se reutilizan los mismos filtros que index(), sin duplicar logica.
        $logs = AuditLog::query()
            ->where('health_center_id', $admin->health_center_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($log) => $this->toArray($log))
            ->values()
            ->all();

        $payload = [
            'exportedAt'     => now()->toIso8601String(),
            'exportedBy'     => ['id' => $admin->id, 'name' => $admin->name],
            'healthCenterId' => $admin->health_center_id,
            'recordCount'    => count($logs),
            'records'        => $logs,
        ];

        // El JSON se serializa UNA sola vez, con flags fijas. La firma se
        // calcula sobre ESTOS bytes exactos - si alguien reserializa el
        // JSON de otra forma (distinto orden de llaves, distinto espaciado),
        // la firma no coincidiria, aunque el contenido "diga" lo mismo.
        $jsonBytes = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $jsonBytes, config('audit.export_signing_key'));

        $this->recordSelfAudit($admin, 'exported_audit_log');

        return response()->json([
            'success' => true,
            'data'    => [
                'payload'          => $payload,
                'signatureAlgo'    => 'HMAC-SHA256',
                'signature'        => $signature,
            ],
        ], 200);
    }

    private function applySort($query, ?string $sortParam): void
    {
        $fields = $sortParam ? explode(',', $sortParam) : ['-occurredAt'];

        foreach ($fields as $field) {
            $direction = 'asc';
            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            if (! array_key_exists($field, self::SORTABLE)) {
                abort(400, "El campo de ordenamiento '{$field}' no es valido.");
            }

            $query->orderBy(self::SORTABLE[$field], $direction);
        }

        $query->orderBy('id', 'asc');
    }

    private function toArray(AuditLog $log): array
    {
        return [
            'id'             => $log->id,
            'userId'         => $log->user_id,
            'userName'       => $log->user?->name,
            'action'         => $log->action,
            'severity'       => $log->severity,
            'resourceType'   => class_basename($log->auditable_type ?? ''),
            'resourceId'     => $log->auditable_id,
            'changes'        => $log->changes,
            'ipAddress'      => $log->ip_address,
            'occurredAt'     => $log->created_at->toIso8601String(),
        ];
    }
}