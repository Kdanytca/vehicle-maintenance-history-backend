<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;

class ListarUsuariosController extends Controller
{
    public function __invoke(Request $request)
    {
        // Obtener todos los usuarios con su rol
        $usuarios = Usuario::with('rol')->paginate(10);
        
        // Retornar vista con los datos
        return view('usuarios.index', compact('usuarios'));
    }
}