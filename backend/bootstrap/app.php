<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('api/*') ? null : '/';
        });

        $middleware->alias([
            'session.active' => \App\Http\Middleware\EnsureMedicalSessionIsActive::class,
            'patient.only'   => \App\Http\Middleware\EnsurePatientToken::class,
            'staff.only'     => \App\Http\Middleware\EnsureStaffToken::class,
        ]);

        // Cuenta cada peticion a la API para /admin/stats. Se aplica a
        // TODAS las rutas api/*, incluidas las publicas, porque el conteo
        // de trafico no depende de si el usuario esta autenticado.
        $middleware->api(prepend: [
            \App\Http\Middleware\TrackApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => ['code' => 'UNAUTHENTICATED', 'message' => 'No autenticado.'],
                ], 401);
            }
        });

        
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $partes = explode('|', $e->getMessage(), 2);

                [$code, $mensaje] = count($partes) === 2
                    ? $partes
                    : ['FORBIDDEN_ROLE', 'No tienes permiso para realizar esta accion.'];

                return response()->json([
                    'success' => false,
                    'error'   => ['code' => $code, 'message' => $mensaje],
                ], 403);
            }
        });

        
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                $partes = explode('|', $e->getMessage(), 2);

                [$code, $mensaje] = count($partes) === 2
                    ? $partes
                    : ['FORBIDDEN_ROLE', 'No tienes permiso para realizar esta accion.'];

                return response()->json([
                    'success' => false,
                    'error'   => ['code' => $code, 'message' => $mensaje],
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => ['code' => 'RESOURCE_NOT_FOUND', 'message' => 'El recurso solicitado no existe.'],
                ], 404);
            }
        });

        $exceptions->render(function (ApiException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => ['code' => $e->errorCode, 'message' => $e->getMessage()],
                ], $e->statusCode);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => ['message' => $e->getMessage() ?: 'Ha ocurrido un error.'],
                ], $e->getStatusCode());
            }
        });
    })->create();