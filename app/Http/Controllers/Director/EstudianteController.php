<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EstudianteController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'dni'          => 'required|string|max:20|unique:students,dni',
            'password'     => 'required|string|min:8',
            'grade_name'   => 'required|string|max:100',
            'section_name' => 'required|string|max:50',
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'dni.required'          => 'El DNI es obligatorio.',
            'dni.unique'            => 'Ese DNI ya está registrado.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
            'grade_name.required'   => 'Selecciona un grado.',
            'section_name.required' => 'Selecciona una sección.',
        ]);

        $schoolId = $this->schoolId();

        $student = DB::transaction(function () use ($request, $schoolId) {

            // 1. Buscar o crear el grado en este colegio
            $grade = Grade::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $request->grade_name]
            );

            // 2. Buscar o crear la sección ligada a ese grado
            //    sections.grade_id es NOT NULL, por eso se pasa como parte de la búsqueda
            $section = Section::firstOrCreate(
                ['grade_id' => $grade->id, 'name' => $request->section_name]
            );

            // 3. Crear el app_user del estudiante
            $user = AppUser::create([
                'school_id' => $schoolId,
                'name'      => $request->name,
                'email'     => null,
                'password'  => Hash::make($request->password),
                'role'      => 'student',
            ]);

            // 4. Crear el estudiante
            $student = Student::create([
                'user_id'    => $user->id,
                'school_id'  => $schoolId,
                'name'       => $request->name,
                'dni'        => $request->dni,
                'grade_id'   => $grade->id,
                'section_id' => $section->id,
            ]);

            $student->load(['grade', 'section', 'qrCode']);
            return $student;
        });

        return response()->json($student, 201);
    }

    public function destroy(int $id)
    {
        $student = Student::where('id', $id)
                          ->where('school_id', $this->schoolId())
                          ->firstOrFail();

        DB::transaction(function () use ($student) {
            $userId = $student->user_id;
            $student->delete();
            AppUser::destroy($userId);
        });

        return response()->json(['message' => 'Estudiante eliminado.']);
    }
}