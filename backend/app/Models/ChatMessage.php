<?php

namespace App\Models;

use App\Enums\MessageOrigin;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'medical_session_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'message_type',
        'body',
        'origin',
        'status',
        'sent_at',
        'confirmed_by_patient_at',
        'delivered_at',
        'read_at',
        'pictogram_id',
    ];

    protected $casts = [
        'message_type'             => MessageType::class,
        'origin'                   => MessageOrigin::class,
        'status'                   => MessageStatus::class,
        'sent_at'                  => 'datetime',
        'confirmed_by_patient_at'  => 'datetime',
        'delivered_at'             => 'datetime',
        'read_at'                  => 'datetime',
    ];

    public function medicalSession(): BelongsTo
    {
        return $this->belongsTo(MedicalSession::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function pictogram(): BelongsTo
    {
        return $this->belongsTo(Pictogram::class);
    }
}