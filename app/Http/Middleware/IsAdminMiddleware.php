<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Defina sua lógica para identificar um administrador
        // Exemplo: um campo 'is_admin' na tabela users ou um email específico
        // Certifique-se de que o usuário está logado antes de checar Auth::user()
        if (Auth::check() && Auth::user()->email === 'dionatanderesende@gmail.com') { // Adapte esta lógica!
            return $next($request);
        }
        // Se você adicionou um campo 'is_admin' à tabela users:
        // if (Auth::check() && Auth::user()->is_admin) {
        //     return $next($request);
        // }


        // Se não for admin, redireciona ou retorna erro 403
        return redirect()->route('dashboard')->with('error', 'Acesso restrito a administradores.');
        // ou: abort(403, 'Acesso não autorizado.');
    }
}