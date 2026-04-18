<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AppUser;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfesorLoginController extends Controller
{
    public function index()
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || !$user->belongsToEnabledSchool()) {
            Auth::guard('app_user')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('profesor.login')
                ->withErrors(['email' => 'El acceso de tu colegio esta bloqueado por el super admin.']);
        }

        $teacher = Teacher::where('user_id', $user->id)->first();

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
                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles',
                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'
                ];
                return [
                    'id' => $h->id,
                    'dia' => $dias[$h->day],
                    'start_time' => $h->start_time,
                    'end_time' => $h->end_time,
                    'subject' => $h->subject->name,
                    'section' => $h->section->name,
                    'grade' => $h->grade->name,
                ];
            });

        $grados = Grade::where('school_id', $teacher->school_id)
            ->whereHas('schedules', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->with(['sections' => fn ($query) => $query
                ->whereHas('schedules', fn ($scheduleQuery) => $scheduleQuery->where('teacher_id', $teacher->id))
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        $cursos = Subject::where('school_id', $teacher->school_id)
            ->whereHas('schedules', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->orderBy('name')
            ->get();

        return Inertia::render('Profesor/Index', [
            'profesores' => Teacher::all(),
            'cursos' => $cursos,
            'horarios' => $horarios,
            'grados' => $grados,
            'school' => School::find($teacher->school_id),
            'auth' => [
                'user' => $user,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Profesor/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:app_users,email',
            'password' => 'required|string|min:8',
            'specialty' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $user = AppUser::create([
                'school_id' => $request->school_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'teacher',
                'code' => null,
                'can_take_general_attendance' => true,
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'school_id' => $request->school_id,
                'specialty' => $request->specialty,
            ]);
        });

        return redirect()->route('profesor.index')->with('success', 'Profesor registrado');
    }

    public function showLogin()
    {
        if (Auth::guard('app_user')->check()) {
            return redirect()->route('profesor.index');
        }

        return Inertia::render('Profesor/Login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (!Auth::guard('app_user')->attempt($data)) {
            return back()->withErrors([
                'email' => 'Credenciales invalidas.'
            ])->withInput($request->only('email'));
        }

        $user = Auth::guard('app_user')->user();

        if (!$user->belongsToEnabledSchool()) {
            Auth::guard('app_user')->logout();

            return back()->withErrors([
                'email' => 'El acceso de tu colegio esta bloqueado por el super admin.',
            ])->withInput($request->only('email'));
        }

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
