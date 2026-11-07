<?php

use App\Exceptions\RegraDeNegocioException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Regras de negócio violadas no service respondem com o status da exceção (422 por padrão),
        // no formato dos erros de validação.
        $exceptions->render(fn (RegraDeNegocioException $e) => response()->json([
            'message' => $e->getMessage(),
            'errors' => [$e->campo => [$e->getMessage()]],
        ], $e->status));
    })->create();
