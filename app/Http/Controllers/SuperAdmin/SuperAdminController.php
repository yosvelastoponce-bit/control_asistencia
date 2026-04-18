<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('app_user')->check() && Auth::guard('app_user')->user()?->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        return Inertia::render('SuperAdmin/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = AppUser::where('email', $request->email)
            ->where('role', 'superadmin')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Credenciales incorrectas.',
            ])->withInput($request->only('email'));
        }

        Auth::guard('app_user')->login($user);
        $request->session()->regenerate();

        return redirect()->route('super-admin.dashboard');
    }

    public function showRegister()
    {
        return Inertia::render('SuperAdmin/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:app_users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        AppUser::create([
            'school_id' => null,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'superadmin',
            'code' => null,
            'can_take_general_attendance' => false,
        ]);

        return redirect()->route('super-admin.login')->with('success', 'Cuenta creada correctamente.');
    }

    public function logout(Request $request)
    {
        Auth::guard('app_user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }

    public function dashboard()
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || !$user->isSuperAdmin()) {
            return redirect()->route('super-admin.login');
        }

        return Inertia::render('SuperAdmin/Dashboard', [
            'superAdmin' => $user,
            'stats' => [
                'directors' => AppUser::where('role', 'director')->count(),
                'schools' => School::count(),
                'enabled_schools' => School::where('is_access_enabled', true)->count(),
                'blocked_schools' => School::where('is_access_enabled', false)->count(),
            ],
            'directors' => AppUser::where('role', 'director')
                ->with('school')
                ->orderByDesc('id')
                ->get(),
            'schools' => School::query()
                ->withCount(['appUsers', 'teachers', 'students'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeDirector(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:app_users,email',
        ]);

        $director = AppUser::create([
            'school_id' => null,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(24)),
            'role' => 'director',
            'code' => $this->generateDirectorCode(),
            'can_take_general_attendance' => true,
        ]);

        $director->load('school');

        return response()->json($director, 201);
    }

    public function toggleSchoolAccess(Request $request, int $schoolId)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'is_access_enabled' => 'required|boolean',
        ]);

        $school = School::findOrFail($schoolId);
        $school->update([
            'is_access_enabled' => $request->boolean('is_access_enabled'),
        ]);

        return response()->json([
            'id' => $school->id,
            'is_access_enabled' => $school->is_access_enabled,
            'message' => $school->is_access_enabled
                ? 'Acceso habilitado para el colegio.'
                : 'Acceso bloqueado para el colegio.',
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        $user = Auth::guard('app_user')->user();

        abort_unless($user && $user->isSuperAdmin(), 403, 'No autorizado.');
    }

    private function generateDirectorCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (AppUser::where('code', $code)->exists());

        return $code;
    }
}
