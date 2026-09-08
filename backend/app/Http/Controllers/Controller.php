<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SenaVida API',
    description: 'API REST del backend de SenaVida, plataforma de comunicacion inclusiva para personas sordas en contextos de urgencia medica.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000/api/v1',
    description: 'Servidor local de desarrollo'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Token',
    description: 'Token Bearer obtenido en /auth/login'
)]
abstract class Controller
{
    use AuthorizesRequests;
}