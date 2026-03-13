<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DirectorController extends Controller
{
    // ─── Registro ────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return Inertia::render('Director/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'school_name'    => 'required|string|max:255',
            'school_code'    => 'required|string|max:50|unique:schools,code',
            'school_address' => 'required|string|max:500',
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:app_users,email',
            'password'       => 'required|string|min:8|confirmed',
        ], [
            'school_name.required'    => 'El nombre del colegio es obligatorio.',
            'school_code.required'    => 'El código del colegio es obligatorio.',
            'school_code.unique'      => 'Ya existe un colegio con ese código.',
            'school_address.required' => 'La dirección del colegio es obligatoria.',
            'name.required'           => 'El nombre del director es obligatorio.',
            'email.required'          => 'El correo es obligatorio.',
            'email.unique'            => 'Ese correo ya está registrado.',
            'password.min'            => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'      => 'Las contraseñas no coinciden.',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Crear el colegio
            $school = School::create([
                'name'    => $request->school_name,
                'code'    => $request->school_code,
                'address' => $request->school_address,
            ]);

            // 2. Crear el usuario director (rol fijo: director)
            AppUser::create([
                'school_id' => $school->id,
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'director',
            ]);
        });

        return redirect()->route('director.login')
            ->with('success', '¡Registro exitoso! Ya puedes iniciar sesión.');
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return Inertia::render('Director/Login', [
            'success' => session('success'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Solo usuarios con rol director pueden acceder
        $user = AppUser::where('email', $request->email)
                       ->where('role', 'director')
                       ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Credenciales incorrectas o no tienes rol de director.',
            ]);
        }

        Auth::guard('app_user')->login($user);
        $request->session()->regenerate();

        return redirect()->route('director.dashboard');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::guard('app_user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('director.login');
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $user = Auth::guard('app_user')->user();

        if (! $user || $user->role !== 'director') {
            return redirect()->route('director.login');
        }

        $school = School::find($user->school_id);

        // Cargar stats del colegio
        $stats = [
            'profesores'  => \App\Models\Teacher::where('school_id', $school->id)->count(),
            'estudiantes' => \App\Models\Student::where('school_id', $school->id)->count(),
            'cursos'      => \App\Models\Subject::where('school_id', $school->id)->count(),
            'grados'      => \App\Models\Grade::where('school_id', $school->id)->count(),
        ];

        return Inertia::render('Director/Dashboard', [
            'director' => $user,
            'school'   => $school,
            'stats'    => $stats,
        ]);
    }
}