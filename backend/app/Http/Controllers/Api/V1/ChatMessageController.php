<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MessageOrigin;
use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\MedicalSession;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ChatMessageController extends Controller
{
    #[OA\Get(
        path: '/medical-sessions/{id}/messages',
        summary: 'Listar mensajes del chat de una atencion',
        tags: ['Chat'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'cursor', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de mensajes, ordenada cronologicamente')]
    )]
    public function index(Request $request, MedicalSession $medicalSession): JsonResponse
    {
        $this->authorize('view', [ChatMessage::class, $medicalSession]);

        $messages = ChatMessage::where('medical_session_id', $medicalSession->id)
            ->with('pictogram')
            ->orderBy('sent_at')
            ->cursorPaginate(50);

        return response()->json([
            'success' => true,
            'data' => ChatMessageResource::collection($messages),
            'meta' => [
                'perPage'    => 50,
                'nextCursor' => $messages->nextCursor()?->encode(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/medical-sessions/{id}/messages',
        summary: 'Enviar un mensaje al chat de una atencion',
        description: 'El backend deriva senderType, senderId, senderName y origin del usuario autenticado. El cliente nunca los envia.',
        tags: ['Chat'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body', 'messageType'],
                properties: [
                    new OA\Property(property: 'body', type: 'string', example: 'Hola, cual es tu motivo de consulta?'),
                    new OA\Property(property: 'messageType', type: 'string', enum: ['text', 'quick_message', 'pictogram']),
                    new OA\Property(property: 'pictogramId', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Mensaje creado correctamente')]
    )]
    public function store(StoreChatMessageRequest $request, MedicalSession $medicalSession): JsonResponse
    {
        $this->authorize('create', [ChatMessage::class, $medicalSession]);

        $user = $request->user();

        if ($user instanceof Patient) {
            $senderType = 'patient';
            $senderId   = null;
            $senderName = $user->name;
            $origin     = MessageOrigin::Patient;
        } else {
            $senderType = 'staff';
            $senderId   = $user->id;
            $senderName = $user->name;
            $origin     = match ($user->role) {
                'admision'       => MessageOrigin::Admission,
                'categorizacion' => MessageOrigin::Triage,
                'medico'         => MessageOrigin::Doctor,
                default          => MessageOrigin::System,
            };
        }

        $message = ChatMessage::create([
            'medical_session_id'      => $medicalSession->id,
            'sender_type'             => $senderType,
            'sender_id'               => $senderId,
            'sender_name'             => $senderName,
            'message_type'            => $request->validated('messageType'),
            'body'                    => $request->validated('body'),
            'origin'                  => $origin->value,
            'status'                  => MessageStatus::Sent->value,
            'sent_at'                 => now(),
            'confirmed_by_patient_at' => $senderType === 'patient' ? now() : null,
            'pictogram_id'            => $request->validated('pictogramId'),
        ]);

        $message->load('pictogram');

        return response()->json([
            'success' => true,
            'data' => new ChatMessageResource($message),
        ], 201);
    }

    #[OA\Post(
        path: '/messages/{id}/confirm',
        summary: 'Confirmar que el paciente recibio un mensaje',
        description: 'Exclusivo del paciente dueno de la atencion.',
        tags: ['Chat'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Mensaje confirmado')]
    )]
    public function confirm(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('confirm', $message);

        $message->update(['confirmed_by_patient_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => new ChatMessageResource($message),
        ]);
    }

    #[OA\Post(
        path: '/messages/{id}/read',
        summary: 'Marcar un mensaje como leido',
        description: 'Exclusivo del personal de la unidad.',
        tags: ['Chat'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Mensaje marcado como leido')]
    )]
    public function markAsRead(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('markAsRead', $message);

        $message->update([
            'status'  => MessageStatus::Read->value,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => new ChatMessageResource($message),
        ]);
    }
}