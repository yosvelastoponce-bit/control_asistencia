<?php

namespace App\Http\Controllers\Cursos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Subject;

class CursosController extends Controller
{
    //
    public function index(Request $request){
        $cursos = Subjects::orderBy('id', 'DESC')->get();

        return Inertia::render('Profesor/Index', [
            'cursor' => $cursos
        ]);
    }
}
