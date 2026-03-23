<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AppUser;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfesorLoginController extends Controller
{
    //
    public function index()
    {
        $user    = Auth::guard('app_user')->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
     
        // Si el usuario no tiene registro de teacher, redirigir con error
        if (!$teacher) {
            Auth::guard('app_user')->logout();
            return redirect()->route('profesor.login')
                ->withErrors(['email' => 'Este usuario no tiene un perfil de profesor asignado.']);
        }
     
        $horarios = Schedule::where('teacher_id', $teacher->id)
            ->with(['subject', 'section', 'grade'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->map(function ($h) {
                $dias = [
                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
                ];
                return [
                    'id'         => $h->id,
                    'dia'        => $dias[$h->day],
                    'start_time' => $h->start_time,
                    'end_time'   => $h->end_time,
                    'subject'    => $h->subject->name,
                    'section'    => $h->section->name,
                    'grade'      => $h->grade->name,
                ];
            });
     
        return Inertia::render('Profesor/Index', [
            'profesores' => Teacher::all(),
            'cursos'     => Subject::all(),
            'horarios'   => $horarios,
            'auth'       => [
                'user' => $user,
            ],
        ]);
    }
    

    public function create(){
        return Inertia::render('Profesor/Create');
    }

    public function store(Request $request){
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:app_users,email',
            'password'  => 'required|string|min:8',
            'specialty' => 'nullable|string|max:255',
        ]);

        // DB::transaction asegura que si algo falla,
        // ninguna de las dos inserciones se guarda
        DB::transaction(function () use ($request) {

            // 1. Crear el usuario
            $user = AppUser::create([
                // 'school_id' => auth()->user()->school_id,
                'school_id' => $request->school_id,
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'teacher',
            ]);

            // 2. Usar el id del usuario recién creado para el teacher
            Teacher::create([
                'user_id'   => $user->id,
                // 'school_id' => $user->school_id,
                'school_id' => $request->school_id,
                'specialty' => $request->specialty,
            ]);

        });

        return redirect()->route('profesor.index')->with('success', 'Profesor registrado');
    }

    public function showLogin(){
        // Si ya está logueado, redirige directo al index
        if (Auth::guard('app_user')->check()) {
            return redirect()->route('profesor.index');
        }

        return Inertia::render('Profesor/Login');
    }

    public function login(Request $request){
        \Log::info('Request data:', $request->only('email', 'password'));

        $data = $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|string|min:8',
        ]);

        // attempt() busca el usuario y verifica el hash automáticamente
        if (!Auth::guard('app_user')->attempt($data)) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas.'
            ])->withInput($request->only('email'));
        }

        // Login del user
        $request->session()->regenerate();

        return redirect()->route('profesor.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('app_user')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('profesor.login');
    }
}
