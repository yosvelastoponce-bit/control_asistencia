<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfesorController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:app_users,email',
            'password'  => 'required|string|min:8',
            'specialty' => 'nullable|string|max:255',
        ], [
            'name.required'     => 'El nombre es obligatorio.',
            'email.required'    => 'El correo es obligatorio.',
            'email.unique'      => 'Ese correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $schoolId = $this->schoolId();

        $result = DB::transaction(function () use ($request, $schoolId) {
            $user = AppUser::create([
                'school_id' => $schoolId,
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id'   => $user->id,
                'school_id' => $schoolId,
                'specialty' => $request->specialty,
            ]);

            $teacher->load('appUser');
            return $teacher;
        });

        return response()->json($result, 201);
    }

    public function destroy(int $id)
    {
        $teacher = Teacher::where('id', $id)
                          ->where('school_id', $this->schoolId())
                          ->firstOrFail();

        DB::transaction(function () use ($teacher) {
            $userId = $teacher->user_id;
            $teacher->delete();
            AppUser::destroy($userId);
        });

        return response()->json(['message' => 'Profesor eliminado.']);
    }
}