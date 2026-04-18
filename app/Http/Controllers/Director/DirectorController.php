<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DirectorController extends Controller
{
    public function showRegister()
    {
        return Inertia::render('Director/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:50|unique:schools,code',
            'school_address' => 'required|string|max:500',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'director_code' => 'required|string|max:32',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'school_name.required' => 'El nombre del colegio es obligatorio.',
            'school_code.required' => 'El codigo del colegio es obligatorio.',
            'school_code.unique' => 'Ya existe un colegio con ese codigo.',
            'school_address.required' => 'La direccion del colegio es obligatoria.',
            'name.required' => 'El nombre del director es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'director_code.required' => 'El codigo del director es obligatorio.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
        ]);

        DB::transaction(function () use ($request) {
            $director = AppUser::where('role', 'director')
                ->whereNull('school_id')
                ->where('email', $request->email)
                ->where('name', $request->name)
                ->where('code', strtoupper(trim($request->director_code)))
                ->first();

            if (!$director) {
                abort(422, 'Los datos del director o el codigo no coinciden con el registro del super admin.');
            }

            $school = School::create([
                'name' => $request->school_name,
                'code' => $request->school_code,
                'address' => $request->school_address,
                'is_access_enabled' => true,
            ]);

            $director->update([
                'school_id' => $school->id,
                'password' => Hash::make($request->password),
                'can_take_general_attendance' => true,
            ]);
        });

        return redirect()->route('director.login')
            ->with('success', 'Registro exitoso. Ya puedes iniciar sesion.');
    }

    public function showLogin()
    {
        return Inertia::render('Director/Login', [
            'success' => session('success'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = AppUser::where('email', $request->email)
            ->where('role', 'director')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Credenciales incorrectas o no tienes rol de director.',
            ]);
        }

        if (!$user->school_id) {
            return back()->withErrors([
                'email' => 'Tu cuenta de director aun no esta vinculada a un colegio.',
            ]);
        }

        if (!$user->belongsToEnabledSchool()) {
            return back()->withErrors([
                'email' => 'El acceso de tu colegio esta bloqueado por el super admin.',
            ]);
        }

        Auth::guard('app_user')->login($user);
        $request->session()->regenerate();

        return redirect()->route('director.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('app_user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('director.login');
    }

    public function dashboard()
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || $user->role !== 'director') {
            return redirect()->route('director.login');
        }

        if (!$user->belongsToEnabledSchool()) {
            Auth::guard('app_user')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('director.login')->withErrors([
                'email' => 'El acceso de tu colegio esta bloqueado por el super admin.',
            ]);
        }

        $school = School::findOrFail($user->school_id);
        $schoolId = $school->id;

        $stats = [
            'profesores' => Teacher::where('school_id', $schoolId)->count(),
            'estudiantes' => Student::where('school_id', $schoolId)->count(),
            'cursos' => Subject::where('school_id', $schoolId)->count(),
            'grados' => Grade::where('school_id', $schoolId)->count(),
        ];

        $profesores = Teacher::where('school_id', $schoolId)
            ->with('appUser')
            ->get();

        $cursos = Subject::where('school_id', $schoolId)->get();

        $estudiantes = Student::where('school_id', $schoolId)
            ->with(['grade', 'section', 'qrCode'])
            ->orderBy('name')
            ->get();

        $grados = Grade::where('school_id', $schoolId)
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('Director/Dashboard', [
            'director' => $user,
            'school' => array_merge($school->toArray(), [
                'logo_url' => $school->logo_path
                    ? route('school.logo.show', ['school' => $school->id, 'v' => optional($school->created_at)?->timestamp ?? time()])
                    : null,
            ]),
            'stats' => $stats,
            'profesores' => $profesores,
            'cursos' => $cursos,
            'estudiantes' => $estudiantes,
            'grados' => $grados,
        ]);
    }
}
