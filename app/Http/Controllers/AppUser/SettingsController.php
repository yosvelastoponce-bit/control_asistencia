<?php

namespace App\Http\Controllers\AppUser;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        abort_unless($user, 401);
        abort_if($user->isSuperAdmin(), 403, 'Este usuario no puede editar esta configuracion.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('app_users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'Debes ingresar un correo electronico valido.',
            'email.unique' => 'Ese correo electronico ya esta registrado.',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Tus datos fueron actualizados correctamente.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function updateSchool(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        abort_unless($user, 401);
        abort_if($user->isSuperAdmin() || !$user->school_id, 403, 'Este usuario no puede editar un colegio.');

        $school = School::findOrFail($user->school_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('schools', 'code')->ignore($school->id),
            ],
            'address' => 'required|string|max:500',
        ], [
            'name.required' => 'El nombre del colegio es obligatorio.',
            'code.required' => 'El codigo del colegio es obligatorio.',
            'code.unique' => 'Ya existe otro colegio con ese codigo.',
            'address.required' => 'La direccion del colegio es obligatoria.',
        ]);

        $school->update($validated);

        return response()->json([
            'message' => 'Los datos del colegio fueron actualizados correctamente.',
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'address' => $school->address,
            ],
        ]);
    }
}
