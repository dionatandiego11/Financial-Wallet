<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Para proteger a rota

class AdminController extends Controller
{
    // Opcional: Construtor para aplicar middleware de admin
    // public function __construct()
    // {
    //     $this->middleware('admin'); // Você precisaria criar este middleware 'admin'
    // }

    public function listUsers()
    {
        // Proteção básica: só permitir se o usuário logado for um admin específico (ex: ID 1 ou email específico)
        // Em um sistema em produção, usaria roles/permissions.
        if (Auth::id() !== 1 && Auth::user()->email !== 'admin@admin.com') { // Exemplo de verificação de admin
            // abort(403, 'Acesso não autorizado.'); // Ou redirecionar
            return redirect()->route('dashboard')->with('error', 'Acesso não autorizado.');
        }

        $users = User::orderBy('name')->paginate(15); // Pegar todos os usuários, ordenados e paginados
        return view('admin.users.index', compact('users')); // Criaremos esta view
    }
}
