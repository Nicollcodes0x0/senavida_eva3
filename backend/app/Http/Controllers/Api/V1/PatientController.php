<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PatientController extends Controller
{
    /**
     * Registra un paciente nuevo (autorregistro).
     * ENDPOINT PUBLICO: el paciente no tiene cuenta todavia, asi que
     * no se exige token. Protegido con rate limiting contra abuso.
     */
    #[OA\Post(
        path: '/patients',
        summary: 'Autorregistro de paciente',
        description: 'Endpoint publico. El propio paciente crea su ficha, sin necesitar token. Ningun rol clinico puede crear pacientes.',
        tags: ['Pacientes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'national_id', 'national_id_type', 'birth_date', 'communication_preference'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Maria Perez'),
                    new OA\Property(property: 'national_id', type: 'string', example: '12345678-9'),
                    new OA\Property(property: 'national_id_type', type: 'string', enum: ['rut', 'pasaporte'], example: 'rut'),
                    new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: '1997-11-15'),
                    new OA\Property(property: 'health_insurance', type: 'string', example: 'Fonasa B'),
                    new OA\Property(property: 'address', type: 'string', example: 'Av. Siempre Viva 123'),
                    new OA\Property(property: 'phone', type: 'string', example: '+56912345678'),
                    new OA\Property(property: 'primary_health_center', type: 'string', example: 'CESFAM Central'),
                    new OA\Property(property: 'allergies', type: 'string', example: 'Penicilina'),
                    new OA\Property(property: 'health_conditions', type: 'string', example: 'Ninguna'),
                    new OA\Property(property: 'communication_preference', type: 'string', enum: ['senas', 'texto', 'lectura_labial', 'mixto'], example: 'senas'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Paciente registrado correctamente'),
            new OA\Response(response: 422, description: 'Datos invalidos o documento ya registrado'),
            new OA\Response(response: 429, description: 'Demasiados intentos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        // 1. Rate limiting: maximo 5 registros por IP cada 10 minutos
        $throttleKey = 'patient-register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'success' => false,
                'error'   => ['message' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos."],
            ], 429);
        }

        RateLimiter::hit($throttleKey, 600);

        // 2. Validar los datos
        $data = $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'national_id'               => ['required', 'string', 'max:20', 'unique:patients,national_id'],
            'national_id_type'          => ['required', 'in:rut,pasaporte'],
            'birth_date'                => ['required', 'date', 'before:today'],
            'health_insurance'          => ['nullable', 'string', 'max:255'],
            'address'                   => ['nullable', 'string', 'max:255'],
            'phone'                     => ['nullable', 'string', 'max:20'],
            'primary_health_center'     => ['nullable', 'string', 'max:255'],
            'allergies'                 => ['nullable', 'string'],
            'health_conditions'         => ['nullable', 'string'],
            'communication_preference'  => ['required', 'in:senas,texto,lectura_labial,mixto'],
        ]);

        // 3. Crear el paciente
        $patient = Patient::create([
            ...$data,
            'is_active' => true,
        ]);

        // 4. Responder con los datos creados
        return response()->json([
            'success' => true,
            'data'    => [
                'id'                       => $patient->id,
                'name'                     => $patient->name,
                'nationalId'               => $patient->national_id,
                'age'                      => $patient->age,
                'communicationPreference'  => $patient->communication_preference,
                'isActive'                 => $patient->is_active,
            ],
        ], 201);
    }

    /**
     * Lista los pacientes. Solo personal clinico autorizado.
     */
    #[OA\Get(
        path: '/patients',
        summary: 'Listar pacientes',
        description: 'Devuelve los pacientes registrados. Solo accesible para personal clinico (admision, categorizacion, medico, super_admin).',
        tags: ['Pacientes'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado de pacientes'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para ver pacientes'),
        ]
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        $patients = Patient::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $patients->map(fn ($patient) => [
                'id'                      => $patient->id,
                'name'                    => $patient->name,
                'nationalId'              => $patient->national_id,
                'age'                     => $patient->age,
                'communicationPreference' => $patient->communication_preference,
                'isActive'                => $patient->is_active,
            ]),
        ], 200);
    }

    /**
     * Muestra el detalle completo de un paciente.
     */
    #[OA\Get(
        path: '/patients/{id}',
        summary: 'Ver un paciente',
        description: 'Devuelve el detalle completo de un paciente, incluyendo sus contactos de emergencia.',
        tags: ['Pacientes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del paciente', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos del paciente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para ver este paciente'),
            new OA\Response(response: 404, description: 'Paciente no encontrado'),
        ]
    )]
    public function show(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                      => $patient->id,
                'name'                    => $patient->name,
                'nationalId'              => $patient->national_id,
                'nationalIdType'          => $patient->national_id_type,
                'birthDate'               => $patient->birth_date->toDateString(),
                'age'                     => $patient->age,
                'healthInsurance'         => $patient->health_insurance,
                'address'                 => $patient->address,
                'phone'                   => $patient->phone,
                'primaryHealthCenter'     => $patient->primary_health_center,
                'allergies'               => $patient->allergies,
                'healthConditions'        => $patient->health_conditions,
                'communicationPreference' => $patient->communication_preference,
                'isActive'                => $patient->is_active,
                'contacts'                => $patient->contacts->map(fn ($contact) => [
                    'id'           => $contact->id,
                    'name'         => $contact->name,
                    'relationship' => $contact->relationship,
                    'phone'        => $contact->phone,
                ]),
            ],
        ], 200);
    }
}
