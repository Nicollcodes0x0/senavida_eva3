<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepcion de negocio con codigo legible por maquina, segun exige
 * el contrato (S18.2): el frontend DEBE ramificar por "code", nunca
 * por el texto de "message".
 */
class ApiException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }
}