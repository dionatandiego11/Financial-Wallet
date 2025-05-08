<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdminMiddleware::class,
            // Posso adicionar outros aliases de middleware de rota aqui, como:
            // 'outroMiddleware' => \App\Http\Middleware\OutroMiddleware::class,
        ]);

        // Posso usar outros middlewares para adicionar globalmente ou a grupos, por exemplo:
        // $middleware->web(append: [
        //     \App\Http\Middleware\MeuMiddlewareParaGrupoWeb::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();